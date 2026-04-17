<?php

namespace App\Service;

use Vosk\Vosk;
use Vosk\Recognizer;
use Vosk\Model;

class VoskService
{
    private ?Model $model = null;

    public function __construct(string $modelPath)
    {
        if (!class_exists('Vosk\Vosk')) {
            throw new \RuntimeException('Extension Vosk PHP non installée.');
        }
        Vosk::init();
        if (!is_dir($modelPath)) {
            throw new \RuntimeException("Modèle Vosk introuvable dans : $modelPath");
        }
        $this->model = new Model($modelPath);
    }

    public function transcribeFile(string $audioFilePath, float $sampleRate = 16000.0): string
    {
        if (!$this->model) {
            return '';
        }
        $recognizer = new Recognizer($this->model, $sampleRate);
        $fp = fopen($audioFilePath, 'rb');
        if (!$fp) {
            return '';
        }
        $text = '';
        while (!feof($fp)) {
            $data = fread($fp, 4000);
            if ($recognizer->acceptWaveform($data)) {
                $res = json_decode($recognizer->result(), true);
                $text .= $res['text'] ?? '';
            }
        }
        $final = json_decode($recognizer->finalResult(), true);
        $text .= $final['text'] ?? '';
        fclose($fp);
        return trim($text);
    }
}