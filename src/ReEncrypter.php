<?php

namespace Techsemicolon\KeyRotation;

use Illuminate\Support\Str;
use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;

class ReEncrypter
{
    /**
     * Encrypter instance with old APP_KEY
     * 
     * @var Encrypter
     */
    private $oldEncrypter;

    /**
     * Encrypter instance with new APP_KEY
     * 
     * @var Encrypter
     */
    private $newEncrypter;

    /**
     * Initializing the ReEncrypter instance
     */
    public function __construct($oldAppKey)
    {
        // Initiate the setup
        $this->setup($oldAppKey);
    }

    /**
     * Initiate the setup
     * 
     * @param string $oldAppKey
     * 
     * @return void
     */
    private function setup($oldAppKey)
    {
        // Get cipher of encryption
        $cipher = config('app.cipher');

        // Get newly generated app_key
        $newAppKey = config('app.key');

        // Verify the keys
        $oldAppKey = (string)$this->verifyAppKey($oldAppKey);
        $newAppKey = (string)$this->verifyAppKey($newAppKey);
        
        // Initialize encrypter instance for old app key
        $this->oldEncrypter = $this->getEncrypterInstance($oldAppKey, $cipher);
        $this->newEncrypter = $this->getEncrypterInstance($newAppKey, $cipher);

        return $this;
    }

    /**
     * Verify the given app key
     * 
     * @param string $key
     * 
     * @return string
     */
    private function verifyAppKey($key)
    {
        if (Str::startsWith($key, 'base64:')) {
            return base64_decode(substr($key, 7));
        }

        return $key;
    }

    /**
     * Instantiate Encrypter instance based
     * on key and cipher
     * 
     * @param string $key
     * @param string $cipher
     * 
     * @return Encrypter
     */
    private function getEncrypterInstance($key, $cipher)
    {
        return new Encrypter($key, $cipher);   
    }

    /**
     * Encrypt the old encrypted hash
     * 
     * @param string $payloadValue
     * @param bool $serialized
     * 
     * @throws ReEncrypterException
     * @return string
     */
    public function encrypt($payloadValue, $serialized = false)
    {
        try{

            return $this->newEncrypter->encrypt($this->oldEncrypter->decrypt($payloadValue, $serialized), $serialized);
        }
        catch(DecryptException $e){
            throw new ReEncrypterException('Either the passed old APP_KEY is incorrect or payload value is invalid!');
        }
    }
}