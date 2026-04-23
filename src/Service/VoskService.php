<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class VoskService
{
    private string $modelPath;
    private string $projectDir;

    public function __construct(string $modelPath, string $projectDir, private ?LoggerInterface $logger = null)
    {
        $this->modelPath = $modelPath;
        $this->projectDir = $projectDir;
    }

    public function transcribeFile(string $inputPath): string
    {
        $this->log("Transcription demandée pour : $inputPath");

        // Étape 1 : Conversion WAV
        $wavPath = $this->convertToWav($inputPath);
        if ($wavPath === null) {
            $this->log("Échec conversion FFmpeg");
            return 'Erreur de conversion audio.';
        }
        $this->log("Conversion réussie : $wavPath");

        // Étape 2 : Transcription Python
        $text = $this->transcribeWav($wavPath);
        $this->log("Texte transcrit : " . substr($text, 0, 100));

        // Nettoyage
        if (file_exists($wavPath)) {
            unlink($wavPath);
        }

        return $text;
    }

    private function convertToWav(string $inputPath): ?string
    {
        $wavPath = $this->projectDir . '/var/temp_vosk_' . uniqid() . '.wav';

        // Vérifier que le fichier source existe et n'est pas vide
        if (!file_exists($inputPath) || filesize($inputPath) === 0) {
            $this->log("Fichier source manquant ou vide : $inputPath");
            return null;
        }

        $ffmpegPath = 'C:\ffmpeg\ffmpeg-8.1-essentials_build\bin\ffmpeg.exe';

        $cmd = sprintf(
            '"%s" -y -i %s -ar 16000 -ac 1 -f wav -acodec pcm_s16le %s 2>&1',
            $ffmpegPath,
            escapeshellarg($inputPath),
            escapeshellarg($wavPath)
        );
        exec($cmd, $output, $returnCode);

        $this->log("FFmpeg return code: $returnCode, output: " . implode("\n", $output));

        if ($returnCode !== 0) {
            return null;
        }
        if (!file_exists($wavPath) || filesize($wavPath) < 44) {
            $this->log("Fichier WAV vide ou inexistant : $wavPath");
            return null;
        }
        return $wavPath;
    }
    private function transcribeWav(string $wavPath): string
    {
        $pythonCmd = $this->detectPython();
        if ($pythonCmd === null) {
            $this->log("Python introuvable");
            return 'Python non trouvé.';
        }

        $scriptPath = $this->projectDir . '/bin/vosk_transcribe.py';
        if (!file_exists($scriptPath)) {
            $this->createPythonScript($scriptPath);
        }

        $cmd = sprintf(
            '%s %s %s %s 2>&1',
            $pythonCmd,
            escapeshellarg($scriptPath),
            escapeshellarg($this->modelPath),
            escapeshellarg($wavPath)
        );
        exec($cmd, $output, $returnCode);
        $fullOutput = implode("\n", $output);
        $this->log("Python return code: $returnCode, output: $fullOutput");

        // Filtrer les logs Vosk
        $lines = explode("\n", $fullOutput);
        $textLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'LOG') || str_starts_with($line, 'ERREUR')) {
                continue;
            }
            $textLines[] = $line;
        }
        $text = trim(implode(' ', $textLines));
        return $text ?: 'Aucun texte reconnu.';
    }

    private function detectPython(): ?string
    {
        // Chemin absolu prioritaire (votre installation)
        $absolutePath = 'C:\Users\azizl\AppData\Local\Programs\Python\Python313-32\python.exe';
        if (file_exists($absolutePath)) {
            return $absolutePath;
        }

        // Fallback : recherche dans le PATH
        $candidates = ['python', 'python3', 'py'];
        foreach ($candidates as $cmd) {
            exec($cmd . ' --version 2>&1', $out, $code);
            if ($code === 0) {
                return $cmd;
            }
        }
        return null;
    }

    private function createPythonScript(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $script = <<<'PYTHON'
#!/usr/bin/env python3
import sys, json, wave, os

def main():
    if len(sys.argv) < 3:
        sys.exit(1)
    model_path = sys.argv[1]
    wav_path   = sys.argv[2]
    if not os.path.isdir(model_path):
        sys.exit(1)
    try:
        from vosk import Model, KaldiRecognizer
        import logging
        logging.disable(logging.CRITICAL)
        model = Model(model_path)
        wf = wave.open(wav_path, "rb")
        rec = KaldiRecognizer(model, wf.getframerate())
        rec.SetWords(True)
        results = []
        while True:
            data = wf.readframes(4000)
            if not data:
                break
            if rec.AcceptWaveform(data):
                r = json.loads(rec.Result())
                t = r.get("text", "").strip()
                if t:
                    results.append(t)
        r = json.loads(rec.FinalResult())
        t = r.get("text", "").strip()
        if t:
            results.append(t)
        wf.close()
        print(" ".join(results))
    except Exception as e:
        print(f"ERREUR Python: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
PYTHON;
        file_put_contents($path, $script);
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        } else {
            error_log($message);
        }
    }
}