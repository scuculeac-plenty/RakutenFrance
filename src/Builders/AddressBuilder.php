<?php

namespace RakutenFrance\Builders;

use Plenty\Plugin\Log\Loggable;

class AddressBuilder
{
    use Loggable;

    /** @var array */
    private $address = [];

    public function __construct()
    {
    }

    /**
     * Add first and last names.
     *
     * @param string $firstName
     * @param string $lastName
     *
     * @return AddressBuilder
     */
    public function withNames(string $firstName, string $lastName): AddressBuilder
    {
        $this->address['name2'] = $firstName;
        $this->address['name3'] = $lastName;

        return $this;
    }

    /**
     * Add address information.
     *
     * @param string $street
     * @param string $houseNumber
     * @param string $city
     * @param string $zipcode
     *
     * @return AddressBuilder
     */
    public function withAddressInformation(string $street, string $houseNumber, string $city, string $zipcode): AddressBuilder
    {
        $this->address['address1'] = $street;
        $this->address['address2'] = $houseNumber;
        $this->address['town'] = $city;
        $this->address['postalCode'] = $zipcode;

        return $this;
    }

    /**
     * Add address option.
     *
     * @param int    $type
     * @param string $value
     *
     * @return AddressBuilder
     */
    public function withAddressOption(int $type, string $value): AddressBuilder
    {
        if ($this->address['options'] === null) {
            $this->address['options'] = [];
        }

        $option = [
            'typeId' => $type,
            'priority' => 0,
            'value' => $value,
        ];

        $this->address['options'][] = $option;

        return $this;
    }

    /**
     * Add country ID.
     *
     * @param int $countryId
     *
     * @return AddressBuilder
     */
    public function withCountryId(int $countryId): AddressBuilder
    {
        $this->address['countryId'] = $countryId;

        return $this;
    }

    /**
     * Add Contact ID and address type.
     *
     * @param int  $contactId
     * @param int  $typeId
     * @param bool $isPrimary
     *
     * @return AddressBuilder
     */
    public function withContactId(int $contactId, int $typeId, bool $isPrimary = true): AddressBuilder
    {
        $this->address['contactRelations'] = [];

        $contactRelations = [
            'contactId' => $contactId,
            'typeId' => $typeId,
            'isPrimary' => $isPrimary,
        ];

        $this->address['contactRelations'][] = $contactRelations;

        return $this;
    }

    /**
     * Return built Address array.
     *
     * @return array
     */
    public function done(): array
    {
        return $this->address;
    }
}
