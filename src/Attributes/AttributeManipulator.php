<?php

namespace Cirrusidentity\SspUtils\Attributes;

/**
 *
 * Take the arbitrarily complex attribute representations from other systems and
 * flatten and convert to strings which is a format usable by SSP.

 */
class AttributeManipulator
{
    /**
     * Take the attributes from an OAuth2 provider and convert them into the structure used by SSP.
     * @param array $array the attributes to flatten and prefix
     * @param string $prefix The prefix to use
     *
     * @return array the array with the new concatenated keys and all values in an array
     */
    public function prefixAndFlatten(array $array, string $prefix = ''): array
    {
        $result = array();
        /** @psalm-suppress MixedAssignment */
        foreach ($array as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                if ($this->isSimpleSequentialArray($value)) {
                    $result[$prefix . $key] = $this->stringify($value);
                } else {
                    $result = $result + $this->prefixAndFlatten($value, $prefix . $key . '.');
                }
            } else {
                $stringify = $this->stringify($value);
                $result[$prefix . $key] = is_array($stringify) ? $stringify  : array($stringify);
            }
        }
        return $result;
    }

    /**
     * Attempt to stringify the input
     * @param mixed $input if an array stringify the values, removing nulls
     * @return array|string
     */
    protected function stringify($input)
    {
        if (is_bool($input)) {
            return $input ? 'true' : 'false';
        } elseif (is_array($input)) {
            $array = [];
            /** @psalm-suppress MixedAssignment */
            foreach ($input as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $array[$key] = $this->stringify($value);
            }
            return $array;
        } elseif (is_object($input) && !method_exists($input, '__toString')) {
            // If it is an object that doesn't have a toStirng representation
            // try using json
            /** @var array $asArray */
            $asArray = json_decode(json_encode($input), true);
            return $this->stringify($asArray);
        }
        /** @psalm-suppress MixedArgument */
        return strval($input);
    }

    /**
     * Determine if the array is a sequential [ 'a', 'b'] or [ 0 => 'a', 1 => 'b'] array with all values being
     * simple types
     * @param array $array The array to check
     * @return bool true if is sequential and values are simple (not array)
     */
    private function isSimpleSequentialArray(array $array): bool
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($array as $key => $value) {
            if (!is_int($key) || is_array($value)) {
                return false;
            }
        }
        return true;
    }
}
