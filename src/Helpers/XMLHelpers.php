<?php

declare(strict_types=1);

namespace LovaszCC\LaravelInnvoice\Helpers;

use Exception;
use SimpleXMLElement;

final class XMLHelpers
{
    /**
     * Convert SimpleXML object to array recursively
     *
     * @param  SimpleXMLElement  $xml  The SimpleXML element
     * @return array|string The converted array or string value
     */
    public static function xmlToArray(SimpleXMLElement $xml): array|string
    {
        $array = [];

        // Get attributes if any
        $attributes = $xml->attributes();
        if (count($attributes) > 0) {
            foreach ($attributes as $key => $value) {
                $array['@attributes'][$key] = (string) $value;
            }
        }

        // Get children
        $children = $xml->children();

        if (count($children) === 0) {
            // No children, this is a leaf node
            $value = (string) $xml;

            // Check if the value is wrapped in CDATA
            if (preg_match('/<!\[CDATA\[(.*?)\]\]>/s', $value, $matches)) {
                $value = $matches[1];
            }

            return $value;
        }

        // Process children
        foreach ($children as $child) {
            $childName = $child->getName();
            $childValue = self::xmlToArray($child);

            // If this child name already exists, make it an array
            if (isset($array[$childName])) {
                if (! is_array($array[$childName]) || ! isset($array[$childName][0])) {
                    $array[$childName] = [$array[$childName]];
                }
                $array[$childName][] = $childValue;
            } else {
                $array[$childName] = $childValue;
            }
        }

        return $array;
    }

    /**
     * Clean XML string by removing extra whitespace and tabs
     *
     * @param  string  $xmlString  The raw XML string
     * @return string The cleaned XML string
     */
    public static function cleanXmlString(string $xmlString): string
    {
        // Remove tabs and normalize whitespace
        $xmlString = preg_replace('/\t+/', '', $xmlString);
        $xmlString = preg_replace('/\s+/', ' ', $xmlString);

        // Remove empty lines
        $xmlString = preg_replace('/\n\s*\n/', "\n", $xmlString);

        // Trim whitespace around tags
        $xmlString = preg_replace('/>\s+</', '><', $xmlString);

        return trim($xmlString);
    }

    /**
     * Parse XML string to array
     *
     * @param  string  $xmlString  The XML string to parse
     * @return array The parsed array
     */
    public static function parseXmlToArray(string $xmlString): array
    {
        // Clean up the XML string by removing extra whitespace and tabs
        $xmlString = self::cleanXmlString($xmlString);

        // Create SimpleXML object
        $xml = simplexml_load_string($xmlString);

        if ($xml === false) {
            throw new Exception('Failed to parse XML string');
        }

        return self::xmlToArray($xml);
    }

    /**
     * Build XML from a regular array
     *
     * @param  array  $data  The array to convert to XML
     * @return string The generated XML string
     */
    public static function buildXmlFromArray(array $data): string
    {
        return self::arrayToXml($data);
    }

    /**
     * Convert array to XML recursively
     *
     * @param  array  $data  The array to convert
     * @param  string  $rootNodeName  The root node name (default: 'root')
     * @return string The generated XML string
     */
    private static function arrayToXml(array $data, string $rootNodeName = 'root'): string
    {
        $xml = '';

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Handle nested arrays
                if (is_numeric($key)) {
                    // If the key is numeric, use the parent key name
                    $xml .= self::arrayToXml($value, $rootNodeName);
                } else {
                    // If the key is a string, use it as the node name
                    $xml .= '<'.$key.'>';
                    $xml .= self::arrayToXml($value, $key);
                    $xml .= '</'.$key.'>';
                }
            } else {
                // Handle scalar values with CDATA
                $xml .= '<'.$key.'><![CDATA['.$value.']]></'.$key.'>';
            }
        }

        return $xml;
    }
}
