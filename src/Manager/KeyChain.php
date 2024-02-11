<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ParadiseSecurity\Bundle\SapientBundle\CryptographyKeyTypes;
use ParadiseSecurity\Bundle\SapientBundle\Cryptography\Key;
use ParadiseSecurity\Bundle\SapientBundle\Cryptography\KeyInterface;
use ParagonIE\Sapient\CryptographyKeys\SealingPublicKey;
use ParagonIE\Sapient\CryptographyKeys\SealingSecretKey;
use ParagonIE\Sapient\CryptographyKeys\SigningPublicKey;
use ParagonIE\Sapient\CryptographyKeys\SigningSecretKey;

class KeyChain implements KeyChainInterface
{
    /**
     * @var Collection|KeyInterface[]
     */
    private Collection $keys;

    public function __construct(array $keys)
    {
        $this->keys = new ArrayCollection();

        $this->buildKeyChain($keys);
    }

    public function getPublicSealingKey(string $name): SealingPublicKey
    {
        return $this->getSealingKey($name, 'public')->getKey();
    }

    public function getPrivateSealingKey(string $name): SealingSecretKey
    {
        return $this->getSealingKey($name, 'private')->getKey();
    }

    public function getPublicSigningKey(string $name): SigningPublicKey
    {
        return $this->getSigningKey($name, 'public')->getKey();
    }

    public function getPrivateSigningKey(string $name): SigningSecretKey
    {
        return $this->getSigningKey($name, 'private')->getKey();
    }

    private function getSealingKey(string $name, string $modifier): KeyInterface
    {
        $type = CryptographyKeyTypes::SEALING;

        $index = $this->getKeyIndex($name, $modifier, $type);

        return $this->getKey($index);
    }

    private function getSigningKey(string $name, string $modifier): KeyInterface
    {
        $type = CryptographyKeyTypes::SIGNING;

        $index = $this->getKeyIndex($name, $modifier, $type);

        return $this->getKey($index);
    }

    private function getKey(string $index): KeyInterface
    {
        return $this->keys->get($index);
    }

    private function buildKeyChain(array $keys): void
    {
        foreach ($keys as $key) {
            if (is_array($key)) {
                $key = $this->createKey($key);
            }

            if (!($key instanceof KeyInterface)) {
                continue;
            }

            $this->addKey($key);
        }
    }

    private function createKey(array $config): KeyInterface
    {
        return new Key(
            $config['alias'],
            $config['identifier'],
            $config['host'],
            $config['modifier'],
            $config['type'],
            $config['class'],
            $config['key'],
        );
    }

    private function addKey(KeyInterface $key): void
    {
        if (!$this->hasKey($key)) {
            $index = $this->createKeyIndex($key);
            $this->keys->set($index, $key);
        }
    }

    private function removeKey(KeyInterface $key): void
    {
        if ($this->hasKey($key)) {
            $this->keys->removeElement($key);
        }
    }

    private function hasKey(KeyInterface $key): bool
    {
        return $this->keys->contains($key);
    }

    private function createKeyIndex(KeyInterface $key): string
    {
        return $this->getKeyIndex(
            $key->getAlias(),
            $key->getModifier(),
            $key->getType(),
        );
    }

    private function getKeyIndex(string $name, string $modifier, string $type): string
    {
        return $name . '_' . $modifier . '_' . $type;
    }
}
