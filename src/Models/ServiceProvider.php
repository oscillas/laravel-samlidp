<?php declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    protected $fillable = [
        'destination_url',
        'logout_url',
        'certificate',
        'block_encryption_algorithm',
        'key_transport_encryption',
        'query_parameters',
        'encrypt_assertion',
    ];

    public function toSpConfig(): array
    {
        return [
            'destination' => $this->destination_url,
            'logout' => $this->logout_url,
            'certificate' => $this->certificate,
            'query_params' => $this->query_parameters,
            'encrypt_assertion' => $this->encrypt_assertion,
            'block_encryption_algorithm' => $this->block_encryption_algorithm,
            'key_transport_encryption' => $this->key_transport_encryption,
        ];
    }
}
