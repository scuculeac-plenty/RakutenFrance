<?php

namespace RakutenFrance\Services;

use Exception;
use Illuminate\Support\Collection;
use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Account\Address\Models\AddressOption;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Models\Country;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Builders\AddressBuilder;
use RakutenFrance\Configuration\PluginConfiguration;

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
            $country = $this->getCountryId($addressData['country'], $addressData['countryalpha2']);

            $addressBuilder = $addressBuilder->withNames($addressData['firstName'], $addressData['lastName'])
                ->withAddressInformation(
                    $checkedAddress['street'] ?: $addressData['address1'],
                    $checkedAddress['houseNumber'],
                    $addressData['city'],
                    $addressData['zipCode']
                )->withCountryId($country ? $country->id : 10);

            if (!empty($addressData['email'])) {
                $addressBuilder = $addressBuilder->withAddressOption(AddressOption::TYPE_EMAIL, $addressData['email']);
            }
            if (!empty($addressData['phone'])) {
                $addressBuilder = $addressBuilder->withAddressOption(AddressOption::TYPE_TELEPHONE, $addressData['phone']);
            }

            return $this->addressRepository->createAddress($addressBuilder->done());
        } catch (ValidationException $e) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.exception',
                [
                    'error' => $e->getMessageBag(),
                ]
            );
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.exception',
                [
                    'error' => $e->getMessage(),
                ]
            );
        }

        return null;
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
        /** @var LibraryCallContract $libCall */
        $libCall = pluginApp(LibraryCallContract::class);
        $split = $libCall->call(PluginConfiguration::PLUGIN_NAME . '::addressSplit', ['address' => $address]);

        if (!$split['success']) {
            return [];
        }

        return [
            'street' => $split['content']['streetName'],
            'houseNumber' => "{$split['content']['houseNumber']} {$split['content']['additionToAddress2']}"
        ];
    }

    /**
     * Get country ID by country name.
     *
     * @param string|null $country
     * @param string|null $countryISO
     *
     * @return Country|null
     */
    public function getCountryId(string $country = null, string $countryISO = null)
    {
        /** @var Country[] $countriesList */
        $countriesList = $this->countryRepositoryContract->getCountriesList(1, []);
        $countriesListCollection = Collection::make($countriesList); /** @phpstan-ignore-line */

        if ($countryISO) {
            $byCountryISOFound = $countriesListCollection->where('isoCode2', '=', $countryISO)->first();
            if ($byCountryISOFound instanceof Country) {
                return $byCountryISOFound;
            }
        }

        if ($country) {
            $byCountryFound = $countriesListCollection->where('name', '=', $country)->first();
            if ($byCountryFound instanceof Country) {
                return $byCountryFound;
            }
        }

        return null;
    }
}
