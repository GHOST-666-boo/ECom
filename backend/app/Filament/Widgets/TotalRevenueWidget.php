<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalRevenueWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $totalRevenue = Order::whereIn('status', ['confirmed', 'shipped', 'delivered'])
            ->sum('total');

        $confirmedRevenue = Order::where('status', 'confirmed')->sum('total');
        $deliveredRevenue = Order::where('status', 'delivered')->sum('total');

        return [
            Stat::make('Total Revenue', '₹' . number_format($totalRevenue, 2))
                ->description('From confirmed, shipped, and delivered orders')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),

            Stat::make('Confirmed Orders', '₹' . number_format($confirmedRevenue, 2))
                ->description('Orders awaiting shipment')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Delivered Orders', '₹' . number_format($deliveredRevenue, 2))
                ->description('Successfully completed orders')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
        ];
    }
}
