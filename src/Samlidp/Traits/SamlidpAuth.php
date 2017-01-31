<?php

namespace Codegreencreative\Idp\Traits;

use App\User;
use LightSaml\Helper;
use LightSaml\SamlConstants;
use LightSaml\Credential\KeyHelper;
use LightSaml\Model\Protocol\Status;
use LightSaml\Binding\BindingFactory;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Assertion\Conditions;
use LightSaml\Model\Protocol\AuthnRequest;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use LightSaml\Model\Assertion\AuthnContext;
use Codegreencreative\Idp\Traits\SamlidpLog;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Assertion\AuthnStatement;
use Symfony\Component\HttpFoundation\Request;
use LightSaml\Model\Assertion\AudienceRestriction;
use LightSaml\Model\Assertion\SubjectConfirmation;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Assertion\SubjectConfirmationData;

trait SamlidpAuth
{
    use SamlidpLog;

    private $destination;
    private $issuer;
    private $certificate;
    private $private_key;

    /**
     * [samlRequest description]
     *
     * @param  Request $request [description]
     * @param  User    $user    [description]
     * @return [type]           [description]
     */
    protected function samlRequest(Request $request, User $user)
    {
        $xml = gzinflate(base64_decode($request->get('SAMLRequest')));

        $deserializationContext = new DeserializationContext;
        $deserializationContext->getDocument()->loadXML($xml);

        $authn_request = new AuthnRequest;
        $authn_request->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);

        $this->service_provider = $this->getServiceProvider($authn_request);

        // Logging
        $this->samlLog('Service Provider: ' . $authn_request->getAssertionConsumerServiceURL());
        $this->samlLog('Service Provider (base64): ' . $this->service_provider);

        $this->destination = config(sprintf('samlidp.sp.%s.destination', $this->service_provider));
        $this->issuer = url(config('samlidp.issuer_uri'));
        $this->certificate = X509Certificate::fromFile(config('samlidp.crt'));
        $this->private_key = KeyHelper::createPrivateKey(config('samlidp.key'), '', true, XMLSecurityKey::RSA_SHA256);

        return $this->samlResponse($authn_request, $user, $request);
    }

    /**
     * [samlResponse description]
     *
     * @return [type] [description]
     */
    protected function samlResponse($authn_request, User $user, Request $request)
    {
        $response = (new Response)->setIssuer(new Issuer($this->issuer))
            ->setStatus(new Status(new StatusCode('urn:oasis:names:tc:SAML:2.0:status:Success')))
            ->addAssertion($assertion = new Assertion)
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime)
            ->setDestination($this->destination)
            ->setInResponseTo($authn_request->getId());

        $assertion
            ->setId(Helper::generateID())
            ->setIssueInstant(new \DateTime)
            ->setIssuer(new Issuer($this->issuer))
            ->setSignature(new SignatureWriter($this->certificate, $this->private_key))
            ->setSubject(
                (new Subject)
                    ->setNameID((new NameID($user->email, SamlConstants::NAME_ID_FORMAT_EMAIL)))
                    ->addSubjectConfirmation(
                        (new SubjectConfirmation)
                            ->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER)
                            ->setSubjectConfirmationData(
                                (new SubjectConfirmationData())
                                    ->setInResponseTo($authn_request->getId())
                                    ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                                    ->setRecipient($authn_request->getAssertionConsumerServiceURL())
                            )
                    )
            )
            ->setConditions(
                (new Conditions)
                    ->setNotBefore(new \DateTime)
                    ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                    ->addItem(
                        new AudienceRestriction([$authn_request->getIssuer()->getValue()])
                    )
            )
            ->addItem(
                (new AuthnStatement())
                    ->setAuthnInstant(new \DateTime('-10 MINUTE'))
                    ->setSessionIndex(Helper::generateID())
                    ->setAuthnContext(
                        (new AuthnContext())
                            ->setAuthnContextClassRef(SamlConstants::NAME_ID_FORMAT_UNSPECIFIED)
                    )
            );
            // ->addItem(
            //     (new AttributeStatement)
            //         // ->addAttribute(new Attribute(ClaimTypes::EMAIL_ADDRESS, $user->email))

            //         // ->addAttribute((new Attribute(ClaimTypes::EMAIL_ADDRESS, $user->email))
            //         //     ->setNameFormat('urn:oasis:names:tc:SAML:2.0:attrname-format:basic'))

            //         ->addAttribute((new Attribute('Email', $user->email))
            //             ->setNameFormat('urn:oasis:names:tc:SAML:2.0:attrname-format:basic'))

            //         // ->addAttribute((new Attribute('primaryEmail', $user->email))
            //         //     ->setNameFormat('urn:oasis:names:tc:SAML:2.0:attrname-format:basic'))

            //         // ->addAttribute(new Attribute(ClaimTypes::COMMON_NAME, $user->name))

            //         // ->addAttribute(new Attribute('http://schemas.xmlsoap.org/claims/AccessLevel', $user->access_levels_id))
            // );

         return $this->sendSamlResponse($response, $request);
    }

    /**
     * [sendSamlRequest description]
     *
     * @param  Request $request [description]
     * @param  User    $user    [description]
     * @return [type]           [description]
     */
    protected function sendSamlResponse($response, Request $request)
    {
        $bindingFactory = new BindingFactory;
        $postBinding = $bindingFactory->create(SamlConstants::BINDING_SAML2_HTTP_POST);
        $messageContext = new MessageContext();
        $messageContext->setMessage($response)->asResponse();
        $message = $messageContext->getMessage();
        $message->setRelayState($request->RelayState);
        $httpResponse = $postBinding->send($messageContext);

        return $httpResponse->getContent();
    }

    /**
     * [getServiceProvider description]
     *
     * @param  [type] $authn_request [description]
     * @return [type]                [description]
     */
    public function getServiceProvider($authn_request)
    {
        return base64_encode($authn_request->getAssertionConsumerServiceURL());
    }
}