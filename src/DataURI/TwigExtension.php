<?php

/**
 * This Twig extension is released under MIT license
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DataURI;

use DataURI\Data;
use DataURI\Dumper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for data URI, see README for example of use
 * Converts data to the data URI Url scheme
 *
 * @see https://www.ietf.org/rfc/rfc2397.txt
 */
class TwigExtension extends AbstractExtension
{
    /**
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            new TwigFilter('dataUri', array($this, 'dataUri'))
        );
    }

    /**
     *
     * @param mixed     $source     DataURI source
     * @param boolean   $strict     Use strict mode (length output)
     * @param string    $mime       the mime type
     * @param array     $parameters Extra parameters, see rfc
     * @return null
     */
    public function dataUri($source, $strict = true, $mime = null, $parameters = array())
    {
        $data = null;

        try {
            switch (true) {
                case is_resource($source):

                    $data = $this->getDataFromResource($source, $strict, $mime, $parameters);

                    break;
                case is_scalar($source):

                    $data = $this->getDataFromScalar($source, $strict, $mime, $parameters);

                    break;
                default:
                    trigger_error("Tried to convert an unsupported source format", E_USER_WARNING);
                    break;
            }
        } catch (\DataURI\Exception\Exception $e) {

            trigger_error(sprintf("Error while building DataUri : %s", $e->getMessage()), E_USER_WARNING);
        }

        if ($data) {

            return Dumper::dump($data);
        }

        return null;
    }

    /**
     *
     * @param resource     $source
     * @param boolean       $strict
     * @param string        $mime
     * @param array         $parameters
     * @return Data
     */
    protected function getDataFromResource($source, $strict, $mime, Array $parameters)
    {

        $streamData = null;

        while ( ! feof($source)) {
            $streamData .= fread($source, 8192);
        }

        $data =  new Data($streamData, $mime, $parameters, $strict);
        $data->setBinaryData(true);

        return $data;
    }

    /**
     *
     * @param string        $source
     * @param boolean       $strict
     * @param string        $mime
     * @param array         $parameters
     * @return Data
     */
    protected function getDataFromScalar($source, $strict, $mime, $parameters)
    {
        if (filter_var($source, FILTER_VALIDATE_URL) !== false) {
            return Data::buildFromUrl($source, $strict);
        }

        if (@file_exists($source)) {
            return Data::buildFromFile($source, $strict);
        }

        return new Data($source, $mime, $parameters, $strict);
    }
}