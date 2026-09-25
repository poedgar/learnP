<?php

/**
 * A trait is a partial class implementation (i.e., constants, properties, and methods) that can be mixed into one or more existing PHP classes. Traits work double duty: they say what a class can do (like an interface), and they provide a modular implementation (like a class).
*/

trait Geocodable
{
    /** @var string */
    protected $address;

    /** @var \Geocoder\Geocoder */
    protected $geocoder;

    /** @var \Geocoder\Model\AddressCollection */
    protected $geocoderResult;

    public function setGeocoder(\Geocoder\Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
    }

    public function setAddress($address)
    {
        $this->address = $address;
    }

    public function getLatitude()
    {
        if (!isset($this->geocoderResult)) {
            $this->geocodeAddress();
        }

        return $this->geocoderResult->first()->getLatitude();
    }

    public function getLongitude()
    {
        if (!isset($this->geocoderResult)) {
            $this->geocodeAddress();
        }

        return $this->geocoderResult->first()->getLongitude();
    }

    protected function geocodeAddress()
    {
        $this->geocoderResult = $this->geocoder->geocode($this->address);

        return true;
    }
}
