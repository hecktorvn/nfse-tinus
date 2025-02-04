<?php

namespace HDViegas\NFSeTinus;

/**
 * Class for RPS construction and validation of data
 *
 * @category  NFePHP
 * @package   HDViegas\NFSeTinus
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Hilthermann Viegas <hdviegas>
 * @link      http://github.com/hdviegas/nfse-tinus for the canonical source repository
 */

use JsonSchema\Validator as JsonValid;
use HDViegas\NFSeTinus\Common\Factory;
use stdClass;

class Rps implements RpsInterface
{
    /**
     * @var stdClass
     */
    protected $std;
    /**
     * @var string
     */
    protected $ver;
    /**
     * @var string
     */
    protected $jsonschema;
    /**
     * @var stdClass
     */
    private $config;

    /**
     * Constructor
     * @param stdClass $rps
     */
    public function __construct(stdClass $rps = null)
    {
        $this->init($rps);
    }

    /**
     * Add config
     * @param type $config
     */
    public function config(\stdClass $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function render(stdClass $rps = null)
    {
        $this->init($rps);
        $fac = new Factory($this->std);
        if (!empty($this->config)) {
            $fac->addConfig($this->config);
        }
        return $fac->render();
    }

    /**
     * Inicialize properties and valid input
     * @param stdClass $rps
     */
    private function init(stdClass $rps = null)
    {
        if (!empty($rps)) {
            $this->std = $this->propertiesToLower($rps);
            $ver = str_replace('.', '_', $rps->version);
            $this->jsonschema = realpath("../storage/jsonSchemes/v$ver/rps.schema");
            $this->validInputData($this->std);
        }
    }

    /**
     * Change properties names of stdClass to lower case
     * @param stdClass $data
     * @return stdClass
     */
    public static function propertiesToLower(stdClass $data)
    {
        $properties = get_object_vars($data);
        $clone = new stdClass();
        foreach ($properties as $key => $value) {
            if ($value instanceof stdClass) {
                $value = self::propertiesToLower($value);
            }
            $nk = strtolower($key);
            $clone->{$nk} = $value;
        }
        return $clone;
    }

    /**
     * Validation json data from json Schema
     * @param stdClass $data
     * @return boolean
     * @throws \RuntimeException
     */
    protected function validInputData($data)
    {
        if (!is_file($this->jsonschema)) {
            return true;
        }
        $validator = new JsonValid();
        $validator->check($data, (object)['$ref' => 'file://' . $this->jsonschema]);
        if (!$validator->isValid()) {
            $msg = "";
            foreach ($validator->getErrors() as $error) {
                $msg .= sprintf("[%s] %s\n", $error['property'], $error['message']);
            }
            throw new \InvalidArgumentException($msg);
        }
        return true;
    }
}
