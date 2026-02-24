<?php

namespace App\Service;

use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeGenerator
{
    private BuilderInterface $builder;

    public function __construct(BuilderInterface $defaultQrCodeBuilder)
    {
        $this->builder = $defaultQrCodeBuilder;
    }

    public function generateRdvDataUri(int $rdvId, string $patientNom, \DateTimeInterface $dateDebut): string
    {
        $payload = [
            'id' => $rdvId,
            'patient' => $patientNom,
            'date' => $dateDebut->format('Y-m-d H:i'),
        ];

        $text = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $this->builder->build(
            writer: new SvgWriter(),
            writerOptions: [],
            validateResult: false,
            data: $text ?: '',
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 240,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 153, 255),
            backgroundColor: new Color(255, 255, 255),
            labelText: '',
            labelFont: null,
            labelAlignment: LabelAlignment::Center,
            labelMargin: null,
            labelTextColor: null,
            logoPath: null,
            logoResizeToWidth: null,
            logoResizeToHeight: null,
            logoPunchoutBackground: null,
        );

        return $result->getDataUri();
    }
}
