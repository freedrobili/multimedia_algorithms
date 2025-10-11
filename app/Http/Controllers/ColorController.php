<?php

namespace App\Http\Controllers;

use App\Http\Services\ColorConverterService;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    private $colorConverter;

    public function __construct(ColorConverterService $colorConverter)
    {
        $this->colorConverter = $colorConverter;
    }

    public function index()
    {
        return view('color-converter');
    }

    public function convert(Request $request)
    {
        $request->validate([
            'from_model' => 'required|string',
            'to_model' => 'required|string',
            'values' => 'required|array'
        ]);

        try {
            $result = $this->colorConverter->convert(
                $request->from_model,
                $request->to_model,
                $request->values
            );

            return response()->json([
                'success' => true,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Универсальный метод для конвертации из любой цветовой модели во все остальные
     */
    public function convertFromAny(Request $request)
    {
        $request->validate([
            'model' => 'required|string|in:RGB,CMYK,HSL,HSV,XYZ,LAB,YUV',
            'values' => 'required|array'
        ]);

        try {
            // Получаем все цветовые представления
            $allColors = $this->colorConverter->getAllColorValues(
                $request->model,
                $request->values
            );

            // Получаем HEX представление
            $hexColor = $this->colorConverter->rgbToHex(...$allColors['RGB']);

            // Получаем координаты для цветового круга из HSL
            $circleCoords = $this->colorConverter->hslToCircleCoordinates(...$allColors['HSL']);

            return response()->json([
                'success' => true,
                'colors' => $allColors,
                'hex' => $hexColor,
                'circle' => $circleCoords
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Метод для конвертации из RGB (сохранен для обратной совместимости)
     */
    public function convertFromRgb(Request $request)
    {
        $request->validate([
            'r' => 'required|integer|min:0|max:255',
            'g' => 'required|integer|min:0|max:255',
            'b' => 'required|integer|min:0|max:255'
        ]);

        try {
            $rgb = [$request->r, $request->g, $request->b];

            // Используем новый универсальный метод
            $allColors = $this->colorConverter->getAllColorValues('RGB', $rgb);
            $hexColor = $this->colorConverter->rgbToHex(...$allColors['RGB']);
            $circleCoords = $this->colorConverter->hslToCircleCoordinates(...$allColors['HSL']);

            return response()->json([
                'success' => true,
                'colors' => $allColors,
                'hex' => $hexColor,
                'circle' => $circleCoords
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Получить координаты для цветового круга по HSL значениям
     */
    public function getCircleCoordinates(Request $request)
    {
        $request->validate([
            'h' => 'required|numeric|min:0|max:360',
            's' => 'required|numeric|min:0|max:100',
            'l' => 'required|numeric|min:0|max:100'
        ]);

        try {
            $circleCoords = $this->colorConverter->hslToCircleCoordinates(
                $request->h,
                $request->s,
                $request->l
            );

            return response()->json([
                'success' => true,
                'circle' => $circleCoords
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Получить HEX представление из RGB
     */
    public function getHexFromRgb(Request $request)
    {
        $request->validate([
            'r' => 'required|integer|min:0|max:255',
            'g' => 'required|integer|min:0|max:255',
            'b' => 'required|integer|min:0|max:255'
        ]);

        try {
            $hexColor = $this->colorConverter->rgbToHex(
                $request->r,
                $request->g,
                $request->b
            );

            return response()->json([
                'success' => true,
                'hex' => $hexColor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
