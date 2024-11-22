<?php

namespace App\Services;

use Imagick;
use ImagickDraw;
use ImagickPixel;

class ChartGenerator
{
    private int $width = 800;
    private int $height = 400;
    private int $padding = 40;

    public function generateIndexChart(array $indexData): string
    {
        // Создаем объекты Imagick
        $image = new Imagick();
        $draw = new ImagickDraw();

        // Создаем пустое изображение
        $image->newImage($this->width, $this->height, new ImagickPixel('white'));
        $image->setImageFormat('png');

        // Настраиваем шрифт
        $draw->setFontSize(12);
        $draw->setFontWeight(400);

        // Получаем мин/макс значения для масштабирования
        $scores = array_column($indexData, 'score');
        $maxScore = max($scores);
        $minScore = min($scores);
        $range = $maxScore - $minScore;

        // Рисуем сетку и оси
        $this->drawGrid($draw, $maxScore, $minScore);

        // Рассчитываем точки для графика
        $points = [];
        $plotWidth = $this->width - (2 * $this->padding);
        $step = $plotWidth / (count($indexData) - 1);

        foreach ($indexData as $i => $data) {
            $x = $this->padding + ($i * $step);
            $normalizedY = ($data['score'] - $minScore) / $range;
            $y = $this->height - $this->padding - ($normalizedY * ($this->height - 2 * $this->padding));
            $points[] = ['x' => $x, 'y' => $y];
        }

        // Рисуем линии графика
        for ($i = 1, $iMax = count($points); $i < $iMax; $i++) {
            $draw->setStrokeWidth(2);

            // Определяем цвет линии (зеленый если растет, красный если падает)
            if ($points[$i]['y'] < $points[$i-1]['y']) {
                $draw->setStrokeColor(new ImagickPixel('green'));
            } else {
                $draw->setStrokeColor(new ImagickPixel('red'));
            }

            // Рисуем линию
            $draw->line(
                $points[$i-1]['x'],
                $points[$i-1]['y'],
                $points[$i]['x'],
                $points[$i]['y']
            );
        }

        // Добавляем заголовок
        $draw->setFillColor(new ImagickPixel('black'));
        $draw->setFontSize(16);
        $draw->annotation(
            $this->padding,
            25,
            'Композитный индекс'
        );

        // Применяем все нарисованное к изображению
        $image->drawImage($draw);

        // Получаем бинарные данные изображения
        $imageBlob = $image->getImageBlob();

        // Очищаем ресурсы
        $draw->clear();
        $image->clear();

        return $imageBlob;
    }

    private function drawGrid(ImagickDraw $draw, float $maxValue, float $minValue): void
    {
        // Настройки для сетки
        $draw->setStrokeColor(new ImagickPixel('rgb(200,200,200)'));
        $draw->setStrokeWidth(1);

        // Горизонтальные линии и метки
        $steps = 5;
        $valueStep = ($maxValue - $minValue) / $steps;

        for ($i = 0; $i <= $steps; $i++) {
            $y = $this->padding + ($i * ($this->height - 2 * $this->padding) / $steps);

            // Рисуем горизонтальную линию
            $draw->line(
                $this->padding,
                $y,
                $this->width - $this->padding,
                $y
            );

            // Добавляем метку значения
            $value = number_format($maxValue - ($i * $valueStep), 2);
            $draw->setFillColor(new ImagickPixel('black'));
            $draw->annotation(
                5,
                $y + 5,
                $value
            );
        }

        // Вертикальная ось
        $draw->setStrokeColor(new ImagickPixel('black'));
        $draw->line(
            $this->padding,
            $this->padding,
            $this->padding,
            $this->height - $this->padding
        );
    }
}
