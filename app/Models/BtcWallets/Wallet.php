<?php

namespace App\Models\BtcWallets;

use App\Enums\BtcWallets\WalletLabelType;
use App\Enums\BtcWallets\WalletVisibleType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * @property string $address
 * @property string $label
 * @property float $balance
 * @property float $last_price
 * @property float $last_volume
 * @property integer $visible_type
 * @property integer $label_type
 * @property array $diff_percent_history
 * @property float $diff_percent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property WalletBalance[] $balances
 */
class Wallet extends Model
{
    use SoftDeletes, AsSource, Filterable;

    protected $fillable = [
        'last_price',
        'last_volume',
        'address',
        'label',
        'balance',
        'diff_percent',
        'diff_percent_history',
        'visible_type',
        'label_type',
    ];

    protected $allowedSorts = [
        'id',
        'balance',
        'updated_at',
        'diff_percent',
        'visible_type',
        'label_type',
    ];

    protected $casts = [
        'diff_percent_history' => 'array',
    ];

    /**
     * Вид наблюдения за кошельком
     * @return string[]
     */
    public static function getVisibleTypes(): array
    {
        return [
            WalletVisibleType::SUSPICIOUS->value => 'Подозрительный',
            WalletVisibleType::MONITORING->value => 'Под наблюдением',
            WalletVisibleType::IDLE->value => 'Неактивный',
            WalletVisibleType::ACCUMULATING->value => 'Накапливает',
            WalletVisibleType::DISTRIBUTING->value => 'Распределяет',
            WalletVisibleType::INTERESTING->value => 'Интересный',
            WalletVisibleType::IGNORED->value => 'Не интересный',
            WalletVisibleType::WATCHED->value => 'В списке наблюдения',
            WalletVisibleType::NEW->value=> 'Новый',
        ];
    }

    public static function getLabelTypes(): array
    {
        return [
            WalletLabelType::EXCHANGE->value => 'Биржа',
            WalletLabelType::WHALE->value => 'Кит',
            WalletLabelType::MINER->value => 'Майнер',
            WalletLabelType::FUND->value => 'Инвест фонд',
            WalletLabelType::OTCDESK->value => 'OTC-площадка',
            WalletLabelType::BOT->value => 'Бот',
            WalletLabelType::SMARTCONTRACT->value => 'Смарт-контракт',
            WalletLabelType::BRIDGE->value => 'Мост',
            WalletLabelType::MIXER->value => 'Миксер',
            WalletLabelType::SCAM->value => 'Скам',
            WalletLabelType::NFTMARKET->value => 'NFT маркет',
            WalletLabelType::DEX->value => 'DEX (Децентрализованная биржа или агрегатор ликвидности)',
            WalletLabelType::DAO->value => 'DAO	(Децентрализованная автономная организация)',
            WalletLabelType::TREASURY->value => 'Казна (Резервный кошелек (например, у проектов))',
            WalletLabelType::PERSONAL->value => 'Частный',
            WalletLabelType::UNKNOWN->value => 'Неизвестно',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(WalletBalance::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(WalletMetric::class);
    }

    public function getExplorerLink(): string
    {
        return 'https://www.blockchain.com/explorer/addresses/btc/' . $this->address;
    }
}
