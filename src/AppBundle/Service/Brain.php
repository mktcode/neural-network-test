<?php
/**
 * Created by PhpStorm.
 * User: mkt
 * Date: 03.05.17
 * Time: 12:09
 */

namespace AppBundle\Service;


class Brain
{
    private $brain;
    private $brainFile;
    private $inputNeurons;
    private $hiddenNeurons;
    private $outputNeurons;

    private $training;
    private $dataIterator = 0;
    private $data;

    private $kernelRootDir;
    public $trainDir;
    public $testDir;

    public function __construct(int $inputNeurons, array $hiddenNeurons, int $outputNeurons, $kernelRootDir)
    {
        $this->inputNeurons = $inputNeurons;
        $this->hiddenNeurons = $hiddenNeurons;
        $this->outputNeurons = $outputNeurons;

        $this->kernelRootDir = $kernelRootDir;
        $this->dataDir = $this->kernelRootDir . '/../web/data';
        $this->trainDir = $this->dataDir . '/train';
        $this->testDir = $this->dataDir . '/test';
        $this->brainsDir = $this->kernelRootDir . '/../var/brains';
    }

    public function create(string $name)
    {
        $this->brainFile = $this->brainsDir . '/' . $name . '.brain';
        if (file_exists($this->brainFile)) {
            $this->brain = fann_create_from_file($this->brainFile);
        } else {
            $layers = $this->hiddenNeurons;
            array_unshift($layers, $this->inputNeurons);
            array_push($layers, $this->outputNeurons);
            $this->brain = fann_create_standard_array(count($layers), $layers);
            fann_set_activation_function_hidden($this->brain, FANN_SIGMOID_SYMMETRIC);
            fann_set_activation_function_output($this->brain, FANN_SIGMOID_SYMMETRIC);
        }
    }

    public function createTraining($data)
    {
        if (count($data)) {
            $this->data = $data;
            $this->training = fann_create_train_from_callback(
                count($this->data),
                fann_get_num_input($this->brain),
                fann_get_num_output($this->brain), [$this, 'createTrainingCallback']
            );
        }
    }

    function createTrainingCallback($numData) {
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

    public function train($maxEpochs = 5000000, $epochsBetweenReports = 1000, $desiredError = 0.01)
    {
        fann_train_on_data($this->brain, $this->training, $maxEpochs, $epochsBetweenReports, $desiredError);
    }

    public function think($input)
    {
        return fann_run($this->brain, $input);
    }

    public function kill()
    {
        fann_destroy($this->brain);
    }

    public function save()
    {
        fann_save($this->brain, $this->brainFile);
    }
}