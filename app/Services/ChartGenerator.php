<?php

namespace App\Services;

use Graph;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use Carbon\Carbon;
use LinePlot;
use mitoteam\jpgraph\MtJpGraph;

class ChartGenerator
{
    private int $width = 800;
    private int $height = 400;
    private int $padding = 60;

    public function generateIndexChart(array $indexData, string $title): string
    {
        $image = new Imagick();
        $draw = new ImagickDraw();

        $image->newImage($this->width, $this->height, new ImagickPixel('white'));
        $image->setImageFormat('png');

        $draw->setFontSize(12);
        $draw->setFontWeight(400);

        // Получаем мин/макс значения
        $scores = array_column($indexData, 'score');
        $maxScore = max($scores);
        $minScore = min($scores);
        $range = $maxScore - $minScore;

        // Рисуем сетку и оси
        $this->drawGrid($draw, $maxScore, $minScore);

        // Добавляем временные метки
        $this->drawTimeLabels($draw, $indexData);

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

        // Создаем градиентную заливку
        $this->drawGradientFill($image, $draw, $points);

        // Рисуем линии графика
        for ($i = 1, $iMax = count($points); $i < $iMax; $i++) {
            $draw->setStrokeWidth(2);

            if ($points[$i]['y'] < $points[$i-1]['y']) {
                $draw->setStrokeColor(new ImagickPixel('rgb(0,150,0)'));
            } else {
                $draw->setStrokeColor(new ImagickPixel('rgb(150,0,0)'));
            }

            $draw->line(
                $points[$i-1]['x'],
                $points[$i-1]['y'],
                $points[$i]['x'],
                $points[$i]['y']
            );
        }

//      Добавляем заголовок
        $draw->setFillColor(new ImagickPixel('rgb(0,150,0)'));
        $draw->setFontSize(18);
        $draw->annotation(
            $this->padding,
            40,
            $title
        );

        $image->drawImage($draw);

        $imageBlob = $image->getImageBlob();
        $draw->clear();
        $image->clear();

        return $imageBlob;
    }

    private function drawGrid(ImagickDraw $draw, float $maxValue, float $minValue): void
    {
        $draw->setStrokeColor(new ImagickPixel('rgb(220,220,220)'));
        $draw->setStrokeWidth(1);

        $steps = 5;
        $valueStep = ($maxValue - $minValue) / $steps;

        for ($i = 0; $i <= $steps; $i++) {
            $y = $this->padding + ($i * ($this->height - 2 * $this->padding) / $steps);

            $draw->line(
                $this->padding,
                $y,
                $this->width - $this->padding,
                $y
            );

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

    private function drawTimeLabels(ImagickDraw $draw, array $indexData): void
    {
        $draw->setFillColor(new ImagickPixel('black'));
        $draw->setFontSize(9);

        $plotWidth = $this->width - (2 * $this->padding);
        $timeLabelsCount = 5; // Количество меток времени
        $step = floor(count($indexData) / $timeLabelsCount);

        for ($i = 0; $i < count($indexData); $i += $step) {
            if (isset($indexData[$i])) {
                $x = $this->padding + ($i * $plotWidth / (count($indexData) - 1));
                $time = Carbon::parse($indexData[$i]['timestamp'])->format('H:i');

                // Поворачиваем текст для лучшей читаемости
                $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
                $draw->annotation(
                    $x,
                    $this->height - ($this->padding / 2),
                    $time
                );
            }
        }
    }

    private function drawGradientFill(Imagick $image, ImagickDraw $draw, array $points): void
    {
        try {
            // Определяем тренд по последним точкам
            $lastIndex = count($points) - 1;
            $startY = $points[0]['y'];
            $endY = $points[$lastIndex]['y'];

            // Если последняя точка выше первой - тренд растущий
            $isUptrend = $endY < $startY; // Y-координаты инвертированы в графике

            $polygon = new ImagickDraw();

            // Выбираем цвет в зависимости от тренда
            if ($isUptrend) {
                $polygon->setFillColor(new ImagickPixel('rgba(0,150,0,0.1)')); // Зеленый для роста
            } else {
                $polygon->setFillColor(new ImagickPixel('rgba(150,0,0,0.1)')); // Красный для падения
            }

            $polygon->setStrokeOpacity(0);

            // Начинаем путь
            $polygon->pathStart();

            // Двигаемся к начальной точке (нижний левый угол)
            $polygon->pathMoveToAbsolute($this->padding, $this->height - $this->padding);

            // Рисуем линию через все точки графика
            foreach ($points as $point) {
                $polygon->pathLineToAbsolute($point['x'], $point['y']);
            }

            // Линия к нижнему правому углу
            $polygon->pathLineToAbsolute($this->width - $this->padding, $this->height - $this->padding);

            // Замыкаем путь
            $polygon->pathClose();

            // Рисуем заполненный путь
            $image->drawImage($polygon);

            $polygon->clear();
        } catch (\Exception $e) {
            // Если что-то пошло не так, просто пропускаем заливку
        }
    }

    public function generateLongShortJpGraph(array $chartData, string $title = 'PNL Лонг/Шорт'): string
    {
        // Подключаем JpGraph
//        require_once base_path('vendor/mitoteam/jpgraph/src/jpgraph.php');
//        require_once base_path('vendor/mitoteam/jpgraph/src/jpgraph_line.php');

        MtJpGraph::load();

        $timestamps = array_column($chartData, 'timestamp');
        $longs = array_column($chartData, 'long');
        $shorts = array_column($chartData, 'short');

        // Создаём график
        $graph = new Graph(800, 400);
        $graph->SetScale('textlin');

        // Оформление
        $graph->img->SetMargin(60, 20, 40, 60);
        $graph->title->Set($title);
        $graph->xaxis->SetTickLabels($timestamps);
        $graph->xaxis->SetLabelAngle(45);
        $graph->xaxis->SetTitle('Время', 'center');
        $graph->yaxis->SetTitle('PNL', 'middle');

        // Линия лонга
        $longPlot = new LinePlot($longs);
        $longPlot->SetColor('green');
        $longPlot->SetLegend('Лонг');

        // Линия шорта
        $shortPlot = new LinePlot($shorts);
        $shortPlot->SetColor('red');
        $shortPlot->SetLegend('Шорт');

        // Добавляем линии на график
        $graph->Add($longPlot);
        $graph->Add($shortPlot);

        // Легенда
        $graph->legend->SetFrameWeight(1);
        $graph->legend->SetPos(0.5, 0.05, 'center', 'top');

        // Сохраняем в файл
        $filename = storage_path('app/public/long_short_' . date('Y-m-d_H-i-s') . '.png');
        $graph->Stroke($filename);

        return $filename;
    }
}
