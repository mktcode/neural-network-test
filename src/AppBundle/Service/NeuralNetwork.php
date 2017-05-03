<?php
/**
 * Created by PhpStorm.
 * User: mkt
 * Date: 03.05.17
 * Time: 12:09
 */

namespace AppBundle\Service;


class NeuralNetwork
{
    private $kernelRootDir;
    public $trainDir;
    public $testDir;
    private $data;
    private $dataIterator = 0;

    public function __construct($kernelRootDir)
    {
        $this->kernelRootDir = $kernelRootDir;
        $this->trainDir = $this->kernelRootDir . '/../web/data/train/';
        $this->testDir = $this->kernelRootDir . '/../web/data/test/';
        $this->setData();
    }

    public function createBrain($name)
    {
        // network
        if (count($this->data)) {
            $numberOfInputs = count($this->data[0][0]);
            $numberOfOutputs = count($this->data[0][1]);
            $layers = [$numberOfInputs, 50, 100, 50, $numberOfOutputs];
            $n = fann_create_standard_array(count($layers), $layers);

            // training
            $maxEpochs = 5000000;
            $epochsBetweenReports = 1000;
            $desiredError = 0.0001;
            $training = fann_create_train_from_callback(count($this->data), $numberOfInputs, $numberOfOutputs, [$this, 'createTrainCallback']);

            fann_set_activation_function_hidden($n, FANN_SIGMOID_SYMMETRIC);
            fann_set_activation_function_output($n, FANN_SIGMOID_SYMMETRIC);
            fann_train_on_data($n, $training, $maxEpochs, $epochsBetweenReports, $desiredError);

            // save
            $brainFile = $this->kernelRootDir . '/../var/brains/' . $name . '.brain';
            fann_save($n, $brainFile);
            fann_destroy($n);

            return new Brain($brainFile);
        }

        return null;
    }

    function createTrainCallback($numData) {
        $dataSet = [
            'input' => $this->data[$this->dataIterator][0],
            'output' => $this->data[$this->dataIterator][1],
        ];

        if ($numData == $this->dataIterator) {
            $this->dataIterator = 0; // reset
        } else {
            $this->dataIterator += 1; // iterate
        }

        return $dataSet;
    }

    private function setData()
    {
        foreach (glob($this->trainDir . '/*.png') as $key => $path) {
            $smile = (bool) substr_count($path, '.smile.');
            $this->data[] = [self::getImagePixels($path), [(int) $smile]];
        }
    }

    static function getImagePixels($imagePath)
    {
        $pixelColors = [];
        $image = imagecreatefrompng($imagePath);

        if ($image) {
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);

            for ($y = 0; $y < $imageHeight; $y++) {
                for ($x = 0; $x < $imageWidth; $x++) {
                    $pixelColors[] = imagecolorat($image, $x, $y) ? 0 : 1;
                }
            }
        }

        return $pixelColors;
    }
}