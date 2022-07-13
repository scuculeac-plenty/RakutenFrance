<?php

namespace RakutenFrance\Catalogue\Helpers;

/**
 * Class CatalogueSettingsHelper
 * @package RakutenFrance\Catalogue\Helpers
 */
class CatalogueSettingsHelper
{
    /**
     * @var array
     */
    private $settings = [];

    /**
     * @return array
     */
    public function done(): array
    {
        return $this->settings;
    }

    /**
     * @param string $key
     * @param string $label
     * @param array $values
     *
     * @return CatalogueSettingsHelper
     */
    public function setSelection(string $key, string $label, array $values): CatalogueSettingsHelper
    {
        $this->settings[] = [
            'key' => $key,
            'label' => $label,
            'type' => 'select',
            'values' => $values
            // values example [['caption' =>'test1', 'value'=> 1],['caption' =>'test2', 'value'=> 2]];
        ];

        return $this;
    }

    /**
     * @param string $key
     * @param string $label
     * @param bool $defaultValue
     *
     * @return CatalogueSettingsHelper
     */
    public function setToggle(string $key, string $label, bool $defaultValue = false): CatalogueSettingsHelper
    {
        $this->settings[] = [
            'key' => $key,
            'label' => $label,
            'type' => 'toggle',
            'defaultValue' => $defaultValue
        ];

        return $this;
    }

    /**
     * @param string $key
     * @param string $label
     * @param string $defaultValue
     *
     * @return CatalogueSettingsHelper
     */
    public function setText(string $key, string $label, string $defaultValue = ''): CatalogueSettingsHelper
    {
        $this->settings[] = [
            'key' => $key,
            'label' => $label,
            'type' => 'text',
            'defaultValue' => $defaultValue
        ];

        return $this;
    }

    /**
     * @param string $key
     * @param string $label
     * @param int $defaultValue
     *
     * @return CatalogueSettingsHelper
     */
    public function setNumber(string $key, string $label, int $defaultValue = 0): CatalogueSettingsHelper
    {
        $this->settings[] = [
            'key' => $key,
            'label' => $label,
            'type' => 'number',
            'defaultValue' => $defaultValue
        ];

        return $this;
    }

    /**
     * @param string $key
     * @param string $label
     * @param bool $defaultValue
     *
     * @return CatalogueSettingsHelper
     */
    public function setCheckbox(string $key, string $label, bool $defaultValue = false): CatalogueSettingsHelper
    {
        $this->settings[] = [
            'key' => $key,
            'label' => $label,
            'type' => 'checkbox',
            'defaultValue' => $defaultValue
        ];

        return $this;
    }
}
