<?php

use App\Models\Currency;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\HtmlNode;

if (!function_exists('current_user')) {
    /**
     * @return \App\Models\User
     */
    function current_user()
    {
        $user = \Auth::user();
        if (App::runningUnitTests() && $user) {
            $user = $user->fresh();
        }

        return $user;
    }
}

if (!function_exists('russian_date')) {
    function russian_date($date, $default = null)
    {
        if (!$date) {
            return $default;
        }

        return Carbon::parse($date)->format('d.m.Y');
    }
}

if (!function_exists('russian_date_with_month_as_text')) {
    function russian_date_with_month_as_text($date)
    {
        return russian_date_with_format($date, '«%s» %s %s года');
    }
}

if (!function_exists('russian_date_with_month_as_text_no_quotes')) {
    function russian_date_with_month_as_text_no_quotes($date, $format = '%s %s %s г.')
    {
        return russian_date_with_format($date, '%s %s %s г.');
    }
}

if (!function_exists('russian_date_with_format')) {
    function russian_date_with_format($date, $format = '%s %s %s г.')
    {

        $russianMonths = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря',
        ];

        $date = Carbon::parse($date);
        $day = $date->format('d');
        $month = array_get($russianMonths, (int)$date->format('m'), '?');
        $year = $date->format('Y');

        return sprintf($format, $day, $month, $year);

    }
}

if (!function_exists('russian_sum_for_act')) {
    function russian_sum_for_act($sum, $currency = 'RUB')
    {
        $rubley = Currency::ru($currency, (float)$sum);
        $kopeek = Currency::ruKopek($currency, (int)$sum * 100);

        return sprintf('<nobr>%s (%s) %s</nobr>, <nobr>%02d %s</nobr>',
            number_format($sum, 2, ',', ' '),
            php_rutils\RUtils::numeral()->getInWords(floor($sum), true),
            $rubley,
            100 * $sum % 100,
            $kopeek
        );
    }
}

if (!function_exists('russian_sum_for_act')) {
    function russian_sum_for_act($sum, $currency = 'RUB')
    {
        $rubley = Currency::ru($currency, (float)$sum);
        $kopeek = Currency::ruKopek($currency, (int)$sum * 100);

        return sprintf('<nobr>%s (%s) %s</nobr>, <nobr>%02d %s</nobr>',
            number_format($sum, 2, ',', ' '),
            php_rutils\RUtils::numeral()->getInWords(floor($sum), true),
            $rubley,
            100 * $sum % 100,
            $kopeek
        );
    }
}

if (!function_exists('russian_sum_words_only')) {
    function russian_sum_words_only($sum, $currency = 'RUB')
    {
        $rubley = Currency::ru($currency, (float)$sum);
        $kopeek = Currency::ruKopek($currency, (int)$sum * 100);

        return sprintf('%s %s %02d %s',
            php_rutils\RUtils::numeral()->getInWords(floor($sum), true),
            $rubley,
            100 * $sum % 100,
            $kopeek
        );
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string, $encoding = 'UTF-8')
    {
        $strlen = mb_strlen($string, $encoding);
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $then = mb_substr($string, 1, $strlen - 1, $encoding);
        return mb_strtoupper($firstChar, $encoding) . $then;
    }
}

if (!function_exists('foreign_sum_for_act')) {
    function foreign_sum_for_act($sum, $currency = 'RUB')
    {
        $symbol = Currency::symbol($currency);

        return sprintf('<nobr>%s %s</nobr>',
            $symbol,
            number_format($sum, 2, ',', ' ')
        );
    }
}

if (!function_exists('has_russian')) {
    function has_russian($phrase)
    {
        return preg_match('/[а-яА-Я]/', $phrase);
    }
}


if (!function_exists('translit_if_russian')) {
    function translit_if_russian($phrase)
    {
        if (has_russian($phrase)) {
            return php_rutils\RUtils::translit()->translify($phrase);
        }

        return $phrase;
    }
}

if (!function_exists('detranslit_if_english')) {
    function detranslit_if_english($phrase)
    {
        if (preg_match('/[a-zA-Z]/', $phrase)) {
            return php_rutils\RUtils::translit()->detranslify($phrase);
        }

        return $phrase;
    }
}

if (!function_exists('str_word_count_utf8')) {
    function str_word_count_utf8($str)
    {
        return count(preg_split('~[^\p{L}\p{N}\']+~u', $str));
    }
}

if (!function_exists('tryCatchIgnoreSendToSentry')) {
    function tryCatchIgnoreSendToSentry($cb)
    {
        try {
            $cb();
        } catch (\Throwable $e) {
            if (app()->runningUnitTests()) {
                throw $e;
            }
            \Sentry::captureException($e);
        }

    }
}

if (!function_exists('firstPtoSpan')) {
    function firstPtoSpan($text)
    {
        $dom = new Dom;
        $dom->load($text);
        /** @var HtmlNode[] $tags */
        foreach (['p', 'div'] as $tag) {
            $tags = $dom->find($tag);
            if (count($tags) > 0) {
                $changeTag = function () {
                    $this->name = 'span';
                };
                $changeTag->call($tags[0]->tag);
            };
        }
        return (string)$dom;
    }
}

if (!function_exists('date_range')) {
    function date_range(Carbon $from, Carbon $to, $interval = 'day', $inclusive = true, $stepCount = 1)
    {
        if ($from->gt($to)) {
            return null;
        }
        // Clone the date objects to avoid issues, then reset their time
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        // Include the end date in the range
        if ($inclusive) {
            $to->addDay();
        }
        $step = CarbonInterval::$interval($stepCount);
        $period = new DatePeriod($from, $step, $to);
        // Convert the DatePeriod into a plain array of Carbon objects
        $range = [];
        foreach ($period as $day) {
            $range[] = new Carbon($day);
        }
        return !empty($range) ? $range : null;
    }
}


