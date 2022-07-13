<?php

namespace RakutenFrance\Services;

use Exception;
use Plenty\Plugin\Log\Loggable;
use Plenty\Exceptions\ValidationException;
use RakutenFrance\Builders\AddressBuilder;
use Plenty\Modules\Account\Address\Models\Address;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\AddressOption;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;

class AddressService
{
    use Loggable;

    /** @var AddressRepositoryContract */
    private $addressRepository;

    /** @var CountryRepositoryContract */
    private $countryRepositoryContract;

    public function __construct(
        AddressRepositoryContract $addressRepository,
        CountryRepositoryContract $countryRepositoryContract
    ) {
        $this->addressRepository = $addressRepository;
        $this->countryRepositoryContract = $countryRepositoryContract;
    }

    /**
     * Create new plentymarkets address from address data.
     *
     * @param array $addressData
     *
     * @return Address|null
     */
    public function placeAddress(array $addressData)
    {
        try {
            /** @var AddressBuilder $addressBuilder */
            $addressBuilder = pluginApp(AddressBuilder::class);

            $checkedAddress = $this->checkAddress($addressData['address1']);

            $addressBuilder = $addressBuilder->withNames($addressData['firstName'], $addressData['lastName'])
                    ->withAddressInformation($checkedAddress['street'], $checkedAddress['houseNumber'], $addressData['city'], $addressData['zipCode'])
                    ->withCountryId($this->getCountryId($addressData['country']));

            if (!empty($addressData['email'])) {
                $addressBuilder = $addressBuilder->withAddressOption(AddressOption::TYPE_EMAIL, $addressData['email']);
            }
            if (!empty($addressData['phone'])) {
                $addressBuilder = $addressBuilder->withAddressOption(AddressOption::TYPE_TELEPHONE, $addressData['phone']);
            }

            return $this->addressRepository->createAddress($addressBuilder->done());
        } catch (ValidationException $e) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME.'::log.exception',
                [
                    'error' => $e->getMessageBag(),
                ]
            );
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME.'::log.exception',
                [
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Sanitize Address.
     *
     * @param string $address
     *
     * @return array
     */
    private function checkAddress(string $address): array
    {
        $removeNumber = preg_match_all('/\d+/', $address, $matches);

        if ($removeNumber > 0 or empty($address) == true) {
            $houseNumber = $matches[0][0];

            $street = str_replace($houseNumber, '', $address);
        } else {
            $houseNumber = $address;
            $street = $address;
        }

        $address = [
            'houseNumber' => $houseNumber,
            'street' => $street,
        ];

        return $address;
    }

    /**
     * Get country ID by country name.
     *
     * @param string $countryName
     *
     * @return int
     */
    public function getCountryId(string $countryName): int
    {
        $countriesList = $this->countryRepositoryContract->getCountriesList(1, []);
        foreach ($countriesList as $country) {
            if ($country->name === $countryName) {
                return $country->id;
            }
        }

        return 10; // France
    }
}
