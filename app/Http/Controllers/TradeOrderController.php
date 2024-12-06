<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradeOrder;
use Illuminate\Http\RedirectResponse;
use Orchid\Support\Facades\Toast;

class TradeOrderController extends Controller
{
    public function delete(Trade $trade, TradeOrder $order): RedirectResponse
    {
        if ($order->type === 'entry') {
            Toast::error('Нельзя удалить входной ордер');
            return back();
        }

        if ($trade->status !== 'open') {
            Toast::error('Нельзя удалить ордер в закрытой сделке');
            return back();
        }

        $trade->position_size -= $order->size;
        $trade->save();

        $order->delete();
        Toast::success('Ордер успешно удален');

        return back();
    }
}
