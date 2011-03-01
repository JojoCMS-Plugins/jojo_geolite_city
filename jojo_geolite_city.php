<?php
/**
 * Jojo CMS
 *
 * Copyright 2100 Jojo CMS
 *
 * See the enclosed file license.txt for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Harvey Kane <code@ragepank.com>
 * @license http://www.fsf.org/copyleft/lgpl.html GNU Lesser General Public License
 * @link    http://www.jojocms.org JojoCMS
 * @package jojo_geolite_city
 */



class Jojo_Plugin_jojo_geolite_city extends Jojo_Plugin
{
    function get_option($value, $args)
    {
        $name = $args[0];
        if ($name != 'captcha_num_chars') return $value; //ignore everything except captcha_num_chars
        $options = Jojo::getOptions();
        if (!empty($options['captcha_trusted_countries'])) {
            $trusted_str = $options['captcha_trusted_countries'];
            $trusted = explode(',', $trusted_str);
            $my_country = self::getCountryCode();
            foreach ($trusted as $country) {
                if (strtoupper($my_country) == strtoupper($country)) return floor($value / 2);
            }
        }
        return $value;
    }
    
    function lookup($ip=false)
    {
        static $_cache;
        if (!$ip) $ip = Jojo::getIp(); //detect IP from request
        if (isset($_cache[$ip])) {
            $record = unserialize($_cache[$ip]);
        } else {
            foreach (Jojo::listPlugins('external/maxmind_geolite_city/geoipcity.inc') as $include) {
                require_once($include);
                break;
            }
            foreach (Jojo::listPlugins('external/maxmind_geolite_city/geoipregionvars.php') as $include) {
                require_once($include);
                break;
            }
            foreach (Jojo::listPlugins('external/maxmind_geolite_city/GeoLiteCity.dat') as $include) {
                $gi = geoip_open($include, GEOIP_STANDARD);
                break;
            }
            if (!isset($gi)) return false; //City data file does not exist
            $record = geoip_record_by_addr($gi, $ip);
        }
        /*
        print $record->country_code . " " . $record->country_code3 . " " . $record->country_name . "\n";
        print $record->region . " " . $GEOIP_REGION_NAME[$record->country_code][$record->region] . "\n";
        print $record->city . "\n";
        print $record->postal_code . "\n";
        print $record->latitude . "\n";
        print $record->longitude . "\n";
        print $record->dma_code . "\n";
        print $record->area_code . "\n";
        */
        geoip_close($gi);
        $_cache[$ip] = serialize($record);
        return $record;
    }
    
    /* returns the short country code eg 'NZ' */
    function getCountryCode($ip=false)
    {
        $record = self::lookup($ip);
        return $record->country_code;
    }
    
    /* returns the long country name eg 'New Zealand' */
    function getCountryName($ip=false)
    {
        $record = self::lookup($ip);
        return $record->country_name;
    }
    
    /* returns the city name eg 'New Zealand' */
    function getRegionName($ip=false)
    {
        $record = self::lookup($ip);
        return $GEOIP_REGION_NAME[$record->country_code][$record->region];
    }
    
    /* returns the city name eg 'New Zealand' */
    function getCityName($ip=false)
    {
        $record = self::lookup($ip);
        return $record->city;
    }
    
    /* returns the latlong coordinates as an array */
    function getLatLong($ip=false)
    {
        $record = self::lookup($ip);
        return array($record->latitude, $record->longitude);
    }
    
    /* filter for jojo_cart plugin which uses Geo-IP to default the billing / shipping country to the detected country */
    function populate_fields($fields)
    {
        if (empty($fields['billing_country'])) {
            $fields['billing_country'] = self::getCountryCode();
        }
        if (empty($fields['shipping_country'])) {
            $fields['shipping_country'] = self::getCountryCode();
        }
        return $fields;
    }
}