<?php

namespace Src;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use DB;
use GuzzleHttp\Client;

class CbrService
{
    const SOURCE_CBR = 'CBR';
    const SOURCE_OER = 'OpenExchangeRates';

    protected static $url = 'http://www.cbr.ru/scripts/XML_daily.asp';

    public static function crossRate($currencyFrom, $currencyTo, Carbon $date)
    {
        $rateFrom = self::exchangeRateForDate($currencyFrom, $date);
        $rateTo = self::exchangeRateForDate($currencyTo, $date);

        return $rateFrom / $rateTo;
    }

    public static function exchangeRateForDate($currency, Carbon $date)
    {
        if (app()->runningUnitTests() && $date->greaterThan(Carbon::parse('2017-07-20'))) {
            // this is needed for UI tests, where we can't use setTestNow
            $date = Carbon::parse('2017-05-05');
        }

        if ($currency === 'RUB') {
            return 1.0;
        }

        if ($currency === 'EURO') {
            $currency = 'EUR';
        }

        $rate = self::fromDb($currency, $date);

        if (!$rate) {
            if ($currency === 'MXN') {
                self::fetchOpenRates($currency, $date);
            } else {
                self::fetchRates($date);
            }
            $rate = self::fromDb($currency, $date);
        }

        if (!$rate) {
            throw new \RuntimeException("Can't get CBR rate for '$currency' for " . $date->toDateString());
        }

        return $rate->rate;
    }

    public static function convert($sum, $from, $to, $date)
    {
        return $sum * self::crossRate($from, $to, $date);
    }

    private static function fetchRates(Carbon $date)
    {
        $jar = new \GuzzleHttp\Cookie\CookieJar;
        $c = new Client();
        $options = [
            'query' => ['date_req' => $date->format('d/m/Y')],
            'cookies' => $jar,
        ];

        if (app()->runningUnitTests()) {
            $fn = base_path('tests/files/cbr.xml-' . $date->format('d-m-Y'));
            if (!file_exists($fn)) {
                $res = (string)$c->get(self::$url, $options)->getBody();
                file_put_contents($fn, $res);
            }
            /** @noinspection SuspiciousAssignmentsInspection */
            $res = file_get_contents($fn);
        } else {
            $res = (string)$c->get(self::$url, $options)->getBody();
        }
        DB::transaction(function () use ($date, $res) {
            foreach (simplexml_load_string($res)->Valute as $value) {
                $rate = str_replace(',', '.', $value->Value) / $value->Nominal;
                ExchangeRate::where([
                    'source' => self::SOURCE_CBR,
                    'currency' => $value->CharCode,
                    'date' => $date->toDateString(),
                ])->firstOrCreate([
                    'source' => self::SOURCE_CBR,
                    'currency' => $value->CharCode,
                    'date' => $date->toDateString(),
                    'rate' => $rate,
                ]);
            };
        });
    }

    /**
     * @param $currency
     * @param Carbon $date
     * @return mixed
     */
    private static function fromDb($currency, Carbon $date)
    {
        $cbr = ExchangeRate::where('date', $date->toDateString())
            ->where('currency', $currency)
            ->where('source', self::SOURCE_CBR)
            ->first();

        if ($cbr) {
            return $cbr;
        }

        // otherwise take any source
        return ExchangeRate::where('date', $date->toDateString())
            ->where('currency', $currency)
            ->first();
    }

    private static function fetchOpenRates($currency, $date)
    {
        $url = sprintf('https://openexchangerates.org/api/historical/%s.json', $date->format('Y-m-d'));
        $jar = new \GuzzleHttp\Cookie\CookieJar;
        $c = new Client();
        $options = [
            'query' => ['app_id' => env('OPENEXCHANGERATES_APP_ID')],
            'cookies' => $jar,
        ];

        if (app()->runningUnitTests()) {
            $fn = base_path('tests/files/openexchangerates.json');
            if (!file_exists($fn)) {
                $res = (string)$c->get($url, $options)->getBody();
                file_put_contents($fn, $res);
            }
            /** @noinspection SuspiciousAssignmentsInspection */
            $res = file_get_contents($fn);
        } else {
            $res = (string)$c->get($url, $options)->getBody();
        }
        DB::transaction(function () use ($date, $res) {
            $rates = json_decode($res, true)['rates'];
            foreach ($rates as $currency => $rate) {
                $rateToRub = $rates['RUB'] / $rate;
                ExchangeRate::where([
                    'source' => self::SOURCE_OER,
                    'currency' => $currency,
                    'date' => $date->toDateString(),
                ])->firstOrCreate([
                    'source' => self::SOURCE_OER,
                    'currency' => $currency,
                    'date' => $date->toDateString(),
                    'rate' => $rateToRub,
                ]);
            }
        });
    }
}