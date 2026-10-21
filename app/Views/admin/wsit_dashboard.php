<?php
// ---------------------------------------------------------------------------
// Locale-aware helpers (dashboard-local).
// The Material dashboard mockup is Bengali-first, so when the app locale is
// 'bn' we render Bengali digits with lakh grouping (৳ ৩,৪২,৮৫০); in 'en' we
// use standard western formatting with Bengali digits off. All labels follow
// the active app locale (which the admin controls from Settings > Language).
// ---------------------------------------------------------------------------
$isBn = app_locale() === 'bn';

$bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
$enDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
$bnDigitsOnly = static function ($value) use ($bnDigits, $enDigits) {
    return str_replace($enDigits, $bnDigits, (string)$value);
};

// Indian/lakh grouping: 342850 => "3,42,850" (used only for Bengali).
$groupIndian = static function (string $intPart): string {
    if (strlen($intPart) <= 3) {
        return $intPart;
    }
    $last3 = substr($intPart, -3);
    $rest = substr($intPart, 0, -3);
    $groups = [];
    while (strlen($rest) > 2) {
        array_unshift($groups, substr($rest, -2));
        $rest = substr($rest, 0, -2);
    }
    if ($rest !== '') {
        array_unshift($groups, $rest);
    }
    return implode(',', array_merge($groups, [$last3]));
};

$fmtNum = static function ($value, int $decimals = 0) use ($isBn, $bnDigitsOnly, $groupIndian) {
    if (!$isBn) {
        return number_format((float)$value, $decimals);
    }
    // Build the integer part WITHOUT a thousands separator. number_format()
    // would already insert a comma, and $groupIndian() adds the lakh
    // separators itself — feeding it an already-comma'd string produced a
    // doubled comma (৳ ১,,৮৬৫).
    $num = number_format((float)$value, $decimals, '.', '');
    [$intPart, $decPart] = array_pad(explode('.', $num), 2, '');
    $out = $groupIndian($intPart);
    if ($decimals > 0) {
        $decPart = rtrim($decPart, '0');
        if ($decPart !== '') {
            $out .= '.' . $decPart;
        }
    }
    return $bnDigitsOnly($out);
};

$fmtPrice = static fn($value) => '৳ ' . $fmtNum($value, 0);

// Signed percent, e.g. +১৮.২% / −৪.৫%  |  +18.2% / −4.5%
$fmtPct = static function ($value) use ($isBn, $bnDigitsOnly) {
    $sign = $value > 0 ? '+' : ($value < 0 ? '−' : '');
    $num = rtrim(rtrim(number_format(abs((float)$value), 1), '0'), '.');
    if ($isBn) {
        $num = $bnDigitsOnly($num);
    }
    return $sign . $num . '%';
};

$fmtDate = static function (string $date) use ($isBn, $bnDigitsOnly) {
    $ts = strtotime($date);
    if ($ts === false) {
        return '';
    }
    if ($isBn) {
        $dayNames = ['রবি', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহঃ', 'শুক্র', 'শনি'];
        $monthNames = ['', 'জানু', 'ফেব্রু', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টে', 'অক্টো', 'নভে', 'ডিসে'];
        return $bnDigitsOnly((string)(int)date('j', $ts))
            . ' ' . $monthNames[(int)date('n', $ts)]
            . ' (' . $dayNames[(int)date('w', $ts)] . ')';
    }
    return date('M j (D)', $ts);
};

// ---------------------------------------------------------------------------
// UI strings — Bengali (mockup) vs English.
// ---------------------------------------------------------------------------
$t = $isBn ? [
    'liveSync'      => 'লাইভ আপডেট: ২ মিনিট আগে সিংক্রোনাইজড',
    'mainOutlet'    => '• প্রধান আউটলেট',
    'heroSub'       => 'আজকের মেঠো ফ্রেশ অর্গানিক পণ্যের বিক্রয় ও সরবরাহ পরিসংখ্যান একনজরে। সকল কোল্ড-চেইন ও ফার্ম-গেট ডেলিভারি স্বাভাবিক রয়েছে।',
    'xlsx'          => 'অর্ডার শিট (.xlsx)',
    'audit'         => 'ইনভেন্টরি অডিট',
    'campaign'      => 'নতুন ক্যাম্পেইন',
    'greetMorning'  => 'শুভ সকাল',
    'greetAfternoon'=> 'শুভ অপরাহ্ন',
    'greetEvening'  => 'শুভ সন্ধ্যা',
    'revenueLabel'  => 'মোট আয় (চলতি মাস)',
    'vsLastMonth'   => 'গত মাসের তুলনায়',
    'todayOrders'   => 'আজকের নতুন অর্ডার',
    'today'         => 'আজ',
    'yesterday'     => 'গতকাল',
    'aov'           => 'গড় অর্ডার মূল্য (AOV)',
    'orders'        => 'অর্ডার',
    'avgAllOrders'  => 'সকল অর্ডারের গড় মূল্য',
    'deliveryRate'  => 'ডেলিভারি সম্পন্ন হার',
    'completed'     => 'সম্পন্ন',
    'cancelled'     => 'বাতিল',
    'chartTitle'    => 'আয় ও অর্ডার বিশ্লেষণ',
    'chartSub'      => 'দৈনিক বিক্রয় ভলিউম বনাম মোট অর্গানিক পণ্য প্রেরণ',
    'chartSubWeekly' => 'সাপ্তাহিক বিক্রয় ভলিউম বনাম মোট অর্গানিক পণ্য প্রেরণ',
    'chartSubMonthly' => 'মাসিক বিক্রয় ভলিউম বনাম মোট অর্গানিক পণ্য প্রেরণ',
    'chartSubYearly' => 'বার্ষিক বিক্রয় ভলিউম বনাম মোট অর্গানিক পণ্য প্রেরণ',
    'daily'         => 'দৈনিক',
    'weekly'        => 'সাপ্তাহিক',
    'monthly'       => 'মাসিক',
    'yearly'        => 'বার্ষিক',
    'revenueSeries' => 'রাজস্ব (৳)',
    'ordersSeries'  => 'অর্ডার',
    'ordersColon'   => 'অর্ডার:',
    'growthColon'   => 'গ্রোথ:',
    'unit'          => 'টি',
    'bestDay'       => 'সর্বোচ্চ বিক্রির দিন:',
    'deliveries'    => 'ডেলিভারি',
    'growthCat'     => 'শীর্ষ প্রবৃদ্ধি ক্যাটাগরি:',
    'growthCatVal'  => 'খাঁটি খেজুরের গুড় ও পাটালি',
    'donutTitle'    => 'পেমেন্ট চ্যানেল অনুপাত',
    'donutSub'      => 'ক্যাশ বনাম ডিজিটাল পেমেন্ট রেশিও',
    'totalTx'       => 'মোট ট্রানজেকশন',
    'successful'    => 'টি সফল',
    'cod'           => 'ক্যাশ অন ডেলিভারি (COD)',
    'parcels'       => 'টি পার্সেল',
    'mfs'           => 'বিকাশ ও নগদ (MFS)',
    'online'        => 'কার্ড ও অনলাইন পেমেন্ট',
    'topProducts'   => 'শীর্ষ বিক্রীত অর্গানিক পণ্যসমূহ',
    'topProductsSub'=> 'গত ৩০ দিনের সর্বাধিক চাহিদাসম্পন্ন কোল্ড-প্রেসড ও ফার্ম আইটেম',
    'allProducts'   => 'সকল পণ্য →',
    'noProducts'    => 'এখনো কোনো পণ্য বিক্রি হয়নি।',
    'searchTitle'   => 'সর্বাধিক অনুসন্ধানকৃত শব্দ',
    'searchSub'     => 'স্টোরফ্রন্টে দর্শকদের করা অনুসন্ধান (শেষ ৩০ দিন)',
    'searchEmpty'   => 'এখনো কোনো অনুসন্ধান রেকর্ড হয়নি। দর্শকরা ক্যাটালগে অনুসন্ধান করলে শব্দগুলো এখানে দেখা যাবে।',
    'searchOverview'=> 'অনুসন্ধান কার্যকলাপ',
    'searchLast30'  => 'মোট অনুসন্ধান (৩০ দিন)',
    'searchToday'   => 'আজ',
    'searchAvg'     => 'দৈনিক গড়',
    'ckTitle'       => 'চেকআউট ফানেল',
    'ckSub'         => 'কে চেকআউট পর্যন্ত গেছে — আর কে সত্যিই অর্ডার করেছে',
    'ckReached'     => 'চেকআউটে পৌঁছেছে',
    'ckOrdered'     => 'অর্ডার করেছে',
    'ckLeft'        => 'অর্ডার ছাড়া চলে গেছে',
    'ckGuestsShort' => 'অতিথি:',
    'ckRecentGuests'=> 'সাম্প্রতিক অতিথি ভিজিট',
    'ckGuestsSub'   => 'নিবন্ধিত নয় এমন দর্শক — সর্বশেষ প্রথমে',
    'ckNoGuests'    => 'এখনো কোনো অতিথি ভিজিট আসেনি।',
    'ckVisitor'     => 'অতিথি',
    'ckUniqueGuests'=> 'অনন্য অতিথি',
    'ckDidOrder'    => 'অর্ডার করেছে',
    'ckNoOrder'     => 'ছেড়ে গেছে',
    'ckInCart'      => 'কার্টে',
    'ckNote'        => 'রিফ্রেশ ৩০ মিনিটে একবার গোনা হয়। ট্র্যাকিং এই আপডেট থেকে শুরু।',
    'bestseller'    => 'বেস্টসেলার',
    'seasonal'      => 'সিজনাল',
    'stonePressed'  => 'ঘানিভাঙা',
    'sunDried'      => 'চাক্কিভাঙা',
    'batch'         => 'ব্যাচ: #',
    'sold'          => 'টি বিক্রি',
    'left'          => 'টি বাকি',
    'lowStock'      => 'স্টক কম',
    'reorder'       => 'রি-অর্ডার প্রস্তাবিত',
    'inStock'       => 'পর্যাপ্ত স্টক',
    'regionalTitle' => 'আঞ্চলিক ডেলিভারি ম্যাপিং',
    'regionalSub'   => 'সারা দেশে মেঠো ফ্রেশের পার্সেল সরবরাহ গতি',
    'regionDhaka'   => 'ঢাকা মেট্রো (ইন-হাউস ফ্লীট • ২৪ ঘণ্টা)',
    'regionChit'    => 'চট্টগ্রাম বিভাগ (রেডএক্স ও স্টিডফাস্ট)',
    'regionSylhet'  => 'সিলেট ও রাজশাহী জেলা',
    'regionOther'   => 'অন্যান্য ৬১ জেলা (কুরিয়ার হাব)',
    'parcel'        => 'পার্সেল',
    'turnaround'    => 'ডেলিভারি সারাংশ',
    'recentTitle'   => 'সাম্প্রতিক অর্গানিক অর্ডারসমূহ',
    'newProcessing' => 'টি নতুন প্রক্রিয়াধীন',
    'recentSub'     => 'গ্রাহকের রিয়েল-টাইম অর্ডার স্থিতি ও শিপমেন্ট চালান তৈরি করুন',
    'allOrders'     => 'সকল অর্ডার',
    'pending'       => 'পেন্ডিং',
    'processing'    => 'প্রসেসিং',
    'inTransit'     => 'ডেলিভারির পথে',
    'delivered'     => 'সম্পন্ন',
    'thOrderId'     => 'অর্ডার আইডি',
    'thCustomer'    => 'গ্রাহকের নাম ও ঠিকানা',
    'thItems'       => 'আইটেম ও বিবরণ',
    'thAmount'      => 'মূল্য',
    'thMethod'      => 'পেমেন্ট মেথড',
    'thStatus'      => 'স্থিতি',
    'thAction'      => 'অ্যাকশন',
    'noOrders'      => 'এখনো কোনো অর্ডার আসেনি — প্রথম অর্ডারের অপেক্ষায়।',
    'viewInvoice'   => 'চালান দেখুন',
    'details'       => 'বিস্তারিত',
    'prevPage'      => 'আগের পাতা',
    'nextPage'      => 'পরের পাতা',
    'statusPending'  => 'পেন্ডিং',
    'statusProcess'  => 'প্রসেসিং হচ্ছে',
    'statusShipped'  => 'ডেলিভারির পথে',
    'statusDelivered'=> 'ডেলিভারি সম্পন্ন',
    'statusCancel'   => 'বাতিল',
    'statusOther'    => 'স্ট্যাটাস',
    'payCod'         => 'ক্যাশ অন ডেলিভারি',
    'payBkash'       => 'বিকাশ (পরিশোধিত)',
    'payNagad'       => 'নগদ (পরিশোধিত)',
    'payRocket'      => 'রকেট (পরিশোধিত)',
    'payOther'       => 'অনলাইন পেমেন্ট',
] : [
    'liveSync'      => 'Live update: synced 2 min ago',
    'mainOutlet'    => '• Main outlet',
    'heroSub'       => 'Today\'s sales & supply stats for Metho Fresh organic products at a glance. All cold-chain and farm-gate deliveries are running normally.',
    'xlsx'          => 'Order sheet (.xlsx)',
    'audit'         => 'Inventory audit',
    'campaign'      => 'New campaign',
    'greetMorning'  => 'Good Morning',
    'greetAfternoon'=> 'Good Afternoon',
    'greetEvening'  => 'Good Evening',
    'revenueLabel'  => 'Total Revenue (this month)',
    'vsLastMonth'   => 'vs last month',
    'todayOrders'   => 'New Orders Today',
    'today'         => 'Today',
    'yesterday'     => 'Yesterday',
    'aov'           => 'Average Order Value (AOV)',
    'orders'        => 'orders',
    'avgAllOrders'  => 'Average across all orders',
    'deliveryRate'  => 'Delivery Completion Rate',
    'completed'     => 'completed',
    'cancelled'     => 'cancelled',
    'chartTitle'    => 'Revenue & Orders Analytics',
    'chartSub'      => 'Daily sales volume vs total organic shipments',
    'chartSubWeekly' => 'Weekly sales volume vs total organic shipments',
    'chartSubMonthly' => 'Monthly sales volume vs total organic shipments',
    'chartSubYearly' => 'Yearly sales volume vs total organic shipments',
    'daily'         => 'Daily',
    'weekly'        => 'Weekly',
    'monthly'       => 'Monthly',
    'yearly'        => 'Yearly',
    'revenueSeries' => 'Revenue (৳)',
    'ordersSeries'  => 'Orders',
    'ordersColon'   => 'Orders:',
    'growthColon'   => 'Growth:',
    'unit'          => '',
    'bestDay'       => 'Peak sales day:',
    'deliveries'    => 'deliveries',
    'growthCat'     => 'Top growth category:',
    'growthCatVal'  => 'Premium date jaggery & patali',
    'donutTitle'    => 'Payment Channel Mix',
    'donutSub'      => 'Cash vs digital payment ratio',
    'totalTx'       => 'Total transactions',
    'successful'    => 'successful',
    'cod'           => 'Cash on Delivery (COD)',
    'parcels'       => 'parcels',
    'mfs'           => 'bKash & Nagad (MFS)',
    'online'        => 'Cards & online payments',
    'topProducts'   => 'Top-selling organic products',
    'topProductsSub'=> 'Most in-demand cold-pressed & farm items (last 30 days)',
    'allProducts'   => 'All products →',
    'noProducts'    => 'No products sold yet.',
    'searchTitle'   => 'Most searched terms',
    'searchSub'     => 'Searches performed by visitors on the storefront (last 30 days)',
    'searchEmpty'   => 'No searches recorded yet. When visitors search the catalog, the terms will appear here.',
    'searchOverview'=> 'Search activity',
    'searchLast30'  => 'Total searches (30 days)',
    'searchToday'   => 'Today',
    'searchAvg'     => 'Daily average',
    'ckTitle'       => 'Checkout funnel',
    'ckSub'         => 'Who reached checkout — and who actually ordered',
    'ckReached'     => 'Reached checkout',
    'ckOrdered'     => 'Placed an order',
    'ckLeft'        => 'Left without ordering',
    'ckGuestsShort' => 'Guests:',
    'ckRecentGuests'=> 'Recent guest visits',
    'ckGuestsSub'   => 'Unregistered visitors, newest first',
    'ckNoGuests'    => 'No guest visits yet.',
    'ckVisitor'     => 'Visitor',
    'ckUniqueGuests'=> 'unique visitors',
    'ckDidOrder'    => 'Ordered',
    'ckNoOrder'     => 'Left',
    'ckInCart'      => 'In cart',
    'ckNote'        => 'Refreshes count once per 30 minutes. Tracking started with this update.',
    'bestseller'    => 'Bestseller',
    'seasonal'      => 'Seasonal',
    'stonePressed'  => 'Stone-pressed',
    'sunDried'      => 'Sun-dried',
    'batch'         => 'Batch: #',
    'sold'          => 'sold',
    'left'          => 'left',
    'lowStock'      => 'Low stock',
    'reorder'       => 'Reorder suggested',
    'inStock'       => 'In stock',
    'regionalTitle' => 'Regional Delivery Mapping',
    'regionalSub'   => 'Metho Fresh parcel delivery speed nationwide',
    'regionDhaka'   => 'Dhaka Metro (In-house fleet • 24h)',
    'regionChit'    => 'Chittagong Division (RedX & Steadfast)',
    'regionSylhet'  => 'Sylhet & Rajshahi districts',
    'regionOther'   => 'Other 61 districts (Courier hub)',
    'parcel'        => 'parcels',
    'turnaround'    => 'Delivery summary',
    'recentTitle'   => 'Recent organic orders',
    'newProcessing' => 'new processing',
    'recentSub'     => 'Real-time order status & shipment invoicing',
    'allOrders'     => 'All orders',
    'pending'       => 'Pending',
    'processing'    => 'Processing',
    'inTransit'     => 'In transit',
    'delivered'     => 'Delivered',
    'thOrderId'     => 'Order ID',
    'thCustomer'    => 'Customer & address',
    'thItems'       => 'Items & details',
    'thAmount'      => 'Amount',
    'thMethod'      => 'Payment method',
    'thStatus'      => 'Status',
    'thAction'      => 'Action',
    'noOrders'      => 'No orders yet — waiting for the first order.',
    'viewInvoice'   => 'View invoice',
    'details'       => 'Details',
    'prevPage'      => 'Previous',
    'nextPage'      => 'Next',
    'statusPending'  => 'Pending',
    'statusProcess'  => 'Processing',
    'statusShipped'  => 'In transit',
    'statusDelivered'=> 'Delivered',
    'statusCancel'   => 'Cancelled',
    'statusOther'    => 'Status',
    'payCod'         => 'Cash on Delivery',
    'payBkash'       => 'bKash (Paid)',
    'payNagad'       => 'Nagad (Paid)',
    'payRocket'      => 'Rocket (Paid)',
    'payOther'       => 'Online payment',
];

// Greeting (hero banner) based on the current hour.
$hour = (int)date('G');
$greeting = match (true) {
    $hour < 12  => $t['greetMorning'],
    $hour < 17  => $t['greetAfternoon'],
    default     => $t['greetEvening'],
};
$adminName = user_display_name(current_user(), app_locale());

// ---------------------------------------------------------------------------
// KPI values
// ---------------------------------------------------------------------------
$monthRevenue = (float)($monthRevenue ?? 0);
$revenueChange = (float)($revenueChange ?? 0);
$todayOrders = (int)($todayOrders ?? 0);
$yesterdayOrders = (int)($yesterdayOrders ?? 0);
$todayChange = (float)($todayChange ?? 0);
$aov = (float)($aov ?? 0);
$delivery = is_array($delivery ?? null) ? $delivery : ['delivered' => 0, 'total' => 0, 'rate' => 0.0];
$deliveryRate = (float)($delivery['rate'] ?? 0);
$deliveredCount = (int)($delivery['delivered'] ?? 0);
$statusCounts = is_array($statusCounts ?? null) ? $statusCounts : [];
$totalOrders = (int)($totalOrders ?? 0);
$pendingCount = (int)($pendingCount ?? 0);
$cancelledCount = (int)($statusCounts['cancelled'] ?? 0);
$cancelPct = $totalOrders > 0 ? round(($cancelledCount / $totalOrders) * 100, 1) : 0.0;

// ---------------------------------------------------------------------------
// Payment channel groups (COD / MFS / cards & online)
// ---------------------------------------------------------------------------
$g = [
    'cod'    => ['count' => 0, 'total' => 0.0],
    'mfs'    => ['count' => 0, 'total' => 0.0],
    'online' => ['count' => 0, 'total' => 0.0],
];
$mfsMethods = ['bkash', 'bikash', 'nagad', 'nogod', 'rocket', 'upay', 'mfs'];
foreach ((array)($payRaw ?? []) as $p) {
    $method = strtolower((string)($p['method'] ?? ''));
    if ($method === 'cod') {
        $key = 'cod';
    } elseif (in_array($method, $mfsMethods, true)) {
        $key = 'mfs';
    } else {
        $key = 'online';
    }
    $g[$key]['count'] += (int)($p['count'] ?? 0);
    $g[$key]['total'] += (float)($p['total'] ?? 0);
}
$gTotal = $g['cod']['count'] + $g['mfs']['count'] + $g['online']['count'];
$gAmount = $g['cod']['total'] + $g['mfs']['total'] + $g['online']['total'];
$pctOf = static function (int $count) use ($gTotal): int {
    return $gTotal > 0 ? (int)round(($count / $gTotal) * 100) : 0;
};
$codPct = $pctOf($g['cod']['count']);
$mfsPct = $pctOf($g['mfs']['count']);
$onlinePct = $pctOf($g['online']['count']);

// Donut segments (r=38 => circumference ~238.76)
$C = 238.76;
$segments = [
    ['color' => '#153e2d', 'count' => $g['cod']['count'], 'pct' => $codPct],
    ['color' => '#fe932c', 'count' => $g['mfs']['count'], 'pct' => $mfsPct],
    ['color' => '#4edea3', 'count' => $g['online']['count'], 'pct' => $onlinePct],
];
$segOffset = 0.0;
$donutSvgs = [];
foreach ($segments as $seg) {
    if ($seg['count'] <= 0) {
        continue;
    }
    $len = round($C * ($seg['count'] / max(1, $gTotal)), 2);
    $donutSvgs[] = '<circle cx="50" cy="50" fill="transparent" r="38" stroke="' . $seg['color']
        . '" stroke-dasharray="' . $len . ' ' . $C . '" stroke-dashoffset="' . (-$segOffset)
        . '" stroke-linecap="round" stroke-width="18"></circle>';
    $segOffset += $len;
}
$lakhStr = rtrim(rtrim(number_format($gAmount / 100000, 2, '.', ''), '0'), '.');
$lakhStr = $isBn ? $bnDigitsOnly($lakhStr) : $lakhStr;
// Lakh notation is only meaningful above ৳1,00,000; otherwise show the real amount.
$centerLabel = $gAmount >= 100000
    ? '৳ ' . $lakhStr . 'L'
    : $fmtPrice($gAmount);

// ---------------------------------------------------------------------------
// Revenue & orders chart geometry per period (daily/weekly/monthly/yearly),
// all real data from wsit_Order::timeSeries(). Matches the mockup's SVG viewBox.
// ---------------------------------------------------------------------------
$chartsRaw = is_array($chart ?? null) ? $chart : [];
$period = in_array((string)($chartsRaw['period'] ?? 'daily'), ['daily', 'weekly', 'monthly', 'yearly'], true)
    ? (string)$chartsRaw['period'] : 'daily';
$chartSeries = [
    'daily'   => array_values((array)($chartsRaw['daily'] ?? [])),
    'weekly'  => array_values((array)($chartsRaw['weekly'] ?? [])),
    'monthly' => array_values((array)($chartsRaw['monthly'] ?? [])),
    'yearly'  => array_values((array)($chartsRaw['yearly'] ?? [])),
];

// Daily series also powers the revenue KPI sparkline.
$dailyData = $chartSeries['daily'];
if (count($dailyData) < 2) {
    $dailyData = [];
    for ($i = 6; $i >= 0; $i--) {
        $dailyData[] = ['bucket' => date('Y-m-d', strtotime('-' . $i . ' days')), 'revenue' => 0.0, 'orders' => 0];
    }
}
$maxRev = max(array_map(fn($d) => (float)($d['revenue'] ?? 0), $dailyData));
$maxRev = $maxRev > 0 ? $maxRev : 1.0;

$periodFmt = [
    'daily'   => 'M j (D)',
    'weekly'  => 'M j',
    'monthly' => 'M Y',
    'yearly'  => 'Y',
];

$chartFor = static function (array $data, string $p) use ($isBn, $bnDigitsOnly, $periodFmt): array {
    $rows = array_values($data);
    if (count($rows) === 0) {
        $rows = [['bucket' => date('Y-m-d'), 'revenue' => 0.0, 'orders' => 0]];
    }
    $maxRev = max(array_map(fn($d) => (float)($d['revenue'] ?? 0), $rows));
    $maxRev = $maxRev > 0 ? $maxRev : 1.0;
    $maxOrd = max(array_map(fn($d) => (int)($d['orders'] ?? 0), $rows));
    $maxOrd = $maxOrd > 0 ? (float)$maxOrd : 1.0;

    $X0 = 45;
    $X1 = 740;
    $TOP_Y = 35;
    $BASE_Y = 205;
    $n = count($rows);
    $xs = [];
    for ($i = 0; $i < $n; $i++) {
        $xs[] = $n === 1 ? $X0 : $X0 + ($i / ($n - 1)) * ($X1 - $X0);
    }
    $revPts = [];
    $ordPts = [];
    foreach ($rows as $i => $d) {
        $revPts[] = [$xs[$i], round($BASE_Y - (((float)($d['revenue'] ?? 0)) / $maxRev) * ($BASE_Y - $TOP_Y), 1)];
        $ordPts[] = [$xs[$i], round($BASE_Y - (((int)($d['orders'] ?? 0)) / $maxOrd) * ($BASE_Y - $TOP_Y), 1)];
    }

    $smoothPath = static function (array $pts): string {
        if (count($pts) === 0) {
            return '';
        }
        $d = 'M ' . $pts[0][0] . ' ' . $pts[0][1];
        for ($i = 0; $i < count($pts) - 1; $i++) {
            $midX = round(($pts[$i][0] + $pts[$i + 1][0]) / 2, 1);
            $midY = round(($pts[$i][1] + $pts[$i + 1][1]) / 2, 1);
            $d .= ' Q ' . $pts[$i][0] . ' ' . $pts[$i][1] . ' ' . $midX . ' ' . $midY;
        }
        $last = $pts[count($pts) - 1];
        return $d . ' L ' . $last[0] . ' ' . $last[1];
    };

    $revLinePath = $smoothPath($revPts);
    $revAreaPath = $revLinePath . ' L ' . $revPts[count($revPts) - 1][0] . ' ' . $BASE_Y . ' L ' . $X0 . ' ' . $BASE_Y . ' Z';
    $ordLinePath = $smoothPath($ordPts);

    // Peak (tooltip) point: highest-revenue bucket.
    $peakIdx = 0;
    foreach ($rows as $i => $d) {
        if ((float)($d['revenue'] ?? 0) > (float)($rows[$peakIdx]['revenue'] ?? 0)) {
            $peakIdx = $i;
        }
    }
    $peakRevPt = $revPts[$peakIdx];
    $peakOrdPt = $ordPts[$peakIdx];
    $tooltipLeft = min(84, max(16, round(($peakRevPt[0] / 760) * 100, 1)));

    $peakRevenue = (float)($rows[$peakIdx]['revenue'] ?? 0);
    $peakOrders  = (int)($rows[$peakIdx]['orders'] ?? 0);
    $prevRevenue = $peakIdx > 0 ? (float)($rows[$peakIdx - 1]['revenue'] ?? 0) : 0.0;
    $peakGrowth  = $prevRevenue > 0
        ? round((($peakRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
        : ($peakRevenue > 0 ? 100.0 : 0.0);

    // Axis labels: fractions of the peak revenue (৳/k/L or ৳/হাজার/লক্ষ).
    $gridlines = [
        ['y' => 20, 'f' => 1.0],
        ['y' => 70, 'f' => 0.73],
        ['y' => 120, 'f' => 0.46],
        ['y' => 170, 'f' => 0.189],
    ];
    $gridLabels = [];
    foreach ($gridlines as $gl) {
        $value = $gl['f'] * $maxRev;
        if ($value < 1000) {
            $gridLabels[] = '৳' . ($isBn ? $bnDigitsOnly((string)(int)round($value)) : (string)(int)round($value));
        } else {
            $rounded = round($value, -3);
            if ($rounded >= 100000) {
                $lakh = rtrim(rtrim(number_format($rounded / 100000, 2, '.', ''), '0'), '.');
                $gridLabels[] = '৳' . ($isBn ? $bnDigitsOnly($lakh) : $lakh) . ($isBn ? 'লক্ষ' : 'L');
            } else {
                $k = (int)round($rounded / 1000);
                $gridLabels[] = '৳' . ($isBn ? $bnDigitsOnly((string)$k) : (string)$k) . ($isBn ? 'হাজার' : 'k');
            }
        }
    }

    $labels = [];
    $fmt = $periodFmt[$p] ?? 'M j (D)';
    foreach ($rows as $d) {
        $labels[] = format_date((string)($d['bucket'] ?? ''), $fmt);
    }

    return [
        'rows'        => $rows,
        'maxRev'      => $maxRev,
        'labels'      => $labels,
        'gridlines'   => $gridlines,
        'gridLabels'  => $gridLabels,
        'revLinePath' => $revLinePath,
        'revAreaPath' => $revAreaPath,
        'ordLinePath' => $ordLinePath,
        'peakIdx'     => $peakIdx,
        'peakRevPt'   => $peakRevPt,
        'peakOrdPt'   => $peakOrdPt,
        'tooltipLeft' => $tooltipLeft,
        'peakRevenue' => $peakRevenue,
        'peakOrders'  => $peakOrders,
        'peakGrowth'  => $peakGrowth,
    ];
};

$charts = [];
foreach ($chartSeries as $p => $s) {
    $charts[$p] = $chartFor($s, $p);
}

// ---------------------------------------------------------------------------
// Regional delivery mapping (real parcel counts + percentages) & revenue
// sparkline for the revenue KPI card (100x30 viewBox).
// ---------------------------------------------------------------------------
$regions = is_array($regions ?? null) ? $regions : [];
$regionKeys = ['dhaka', 'chittagong', 'sylhet_rajshahi', 'other'];
$regionTotal = 0;
$regionDelivered = 0;
foreach ($regionKeys as $rk) {
    $regionTotal     += (int)($regions[$rk]['parcels'] ?? 0);
    $regionDelivered += (int)($regions[$rk]['delivered'] ?? 0);
}
$regionPct = static function (int $parcels) use ($regionTotal): int {
    return $regionTotal > 0 ? (int)round(($parcels / $regionTotal) * 100) : 0;
};
$regionUI = [
    'dhaka'          => [$t['regionDhaka'], 'bg-primary-container',   'text-primary',            'bg-primary-container'],
    'chittagong'     => [$t['regionChit'],  'bg-secondary-container', 'text-secondary',          'bg-secondary-container'],
    'sylhet_rajshahi'=> [$t['regionSylhet'],'bg-tertiary-fixed-dim',  'text-on-surface',         'bg-tertiary-fixed-dim'],
    'other'          => [$t['regionOther'], 'bg-surface-variant',     'text-on-surface-variant', 'bg-surface-variant'],
];

$sparkPts = [];
foreach ($dailyData as $i => $d) {
    $sx = count($dailyData) === 1 ? 0 : round(($i / (count($dailyData) - 1)) * 90, 2);
    $sy = round(26 - (((float)$d['revenue']) / $maxRev) * 22, 2);
    $sparkPts[] = [$sx, $sy];
}
$sparkLine = '';
$sparkArea = '';
if (count($sparkPts) > 0) {
    $sparkLine = 'M' . implode(' L', array_map(static fn($p) => $p[0] . ' ' . $p[1], $sparkPts));
    $lastX     = $sparkPts[count($sparkPts) - 1][0];
    $firstX    = $sparkPts[0][0];
    $sparkArea = $sparkLine . ' L ' . $lastX . ' 30 L ' . $firstX . ' 30 Z';
}

// Best growth category (real units sold over the last 30 days vs previous 30).
$growth = is_array($growth ?? null) ? $growth : [];
$growthName = ($growth['name_en'] ?? '') !== '' || ($growth['name_bn'] ?? '') !== ''
    ? ($isBn ? (string)($growth['name_bn'] ?: $growth['name_en']) : (string)($growth['name_en'] ?: $growth['name_bn']))
    : '';
$growthPct = (float)($growth['growth'] ?? 0);

// ---------------------------------------------------------------------------
// Top products (real DB rows, localized)
// ---------------------------------------------------------------------------
$productName = static function (array $p): string {
    if (app_locale() === 'bn') {
        $name = trim((string)($p['name_bn'] ?? ''));
        return $name !== '' ? $name : (string)($p['name_en'] ?? '');
    }
    return (string)($p['name_en'] ?? '');
};
$catBadges = [
    'gur'         => [app_locale() === 'bn' ? 'সিজনাল' : 'Seasonal', 'bg-primary-fixed text-primary font-medium'],
    'patali'      => [app_locale() === 'bn' ? 'সিজনাল' : 'Seasonal', 'bg-primary-fixed text-primary font-medium'],
    'mustard-oil' => [app_locale() === 'bn' ? 'ঘানিভাঙা' : 'Stone-pressed', 'bg-tertiary-fixed text-tertiary-container font-medium'],
    'spices'      => [app_locale() === 'bn' ? 'চাক্কিভাঙা' : 'Sun-dried', 'bg-secondary-fixed/50 text-on-secondary-fixed-variant font-medium'],
];

// ---------------------------------------------------------------------------
// Orders table UI maps (locale-aware labels; identical classes)
// ---------------------------------------------------------------------------
$statusUI = [
    'pending'    => [$t['statusPending'], 'bg-surface-container-high text-on-surface'],
    'processing' => [$t['statusProcess'], 'bg-secondary-fixed text-on-secondary-container'],
    'shipped'    => [$t['statusShipped'], 'bg-primary-fixed text-primary'],
    'delivered'  => [$t['statusDelivered'], 'bg-tertiary-fixed text-tertiary-container'],
    'cancelled'  => [$t['statusCancel'], 'bg-error-container text-on-error-container'],
];
$payUI = [
    'cod'    => [$t['payCod'], 'bg-surface-container-high text-on-surface font-medium', 'local_shipping'],
    'bkash'  => [$t['payBkash'], 'bg-secondary-fixed/40 text-on-secondary-fixed-variant font-semibold', 'account_balance_wallet'],
    'nagad'  => [$t['payNagad'], 'bg-secondary-fixed/40 text-on-secondary-fixed-variant font-semibold', 'account_balance_wallet'],
    'rocket' => [$t['payRocket'], 'bg-secondary-fixed/40 text-on-secondary-fixed-variant font-semibold', 'account_balance_wallet'],
];
$recentOrders = (array)($recentOrders ?? []);
$topProducts = (array)($topProducts ?? []);
$searchStats = (array)($searchStats ?? []);
$searchCount30 = (int)($searchCount30 ?? 0);
$searchCountToday = (int)($searchCountToday ?? 0);
$searchPage = max(1, (int)($searchPage ?? 1));
$searchPages = max(1, (int)($searchPages ?? 1));
$searchPerPage = max(1, (int)($searchPerPage ?? 10));
$searchTermsTotal = max(0, (int)($searchTermsTotal ?? 0));
// Pagination links keep the report period so both widgets stay in sync.
$searchPageUrl = static function (int $n) use ($period): string {
    return locale_url('/mf-dashboard') . '?period=' . rawurlencode((string)$period) . '&search_page=' . $n;
};
// Page-number window: show all pages when few, otherwise 1 … n … last.
$searchNums = $searchPages <= 7
    ? range(1, $searchPages)
    : array_values(array_unique(array_merge(
        [1],
        ($searchPage > 3 ? ['…'] : []),
        range(max(2, $searchPage - 1), min($searchPages - 1, $searchPage + 1)),
        ($searchPage < $searchPages - 2 ? ['…'] : []),
        [$searchPages]
    )));
?>
<div class="flex flex-col gap-space-lg">
<!-- Top Hero & Welcome Banner -->
<section class="w-full rounded-2xl bg-gradient-to-r from-primary via-primary-container to-surface-container-highest p-space-lg text-on-primary shadow-sm relative overflow-hidden">
    <div class="absolute -right-8 -bottom-10 w-72 h-72 rounded-full bg-tertiary-container/30 blur-2xl pointer-events-none"></div>
    <div class="absolute right-40 top-0 w-48 h-48 rounded-full bg-secondary-container/20 blur-xl pointer-events-none"></div>
    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-space-md">
        <div class="flex flex-col gap-space-xs max-w-2xl">
            <div class="flex items-center gap-space-xs">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-fixed/20 backdrop-blur-sm text-primary-fixed font-label-sm text-label-sm">
                    <span class="w-2 h-2 rounded-full bg-tertiary-fixed-dim animate-pulse"></span>
                    <?= e($t['liveSync']) ?>
                </span>
                <span class="text-on-primary-container text-body-sm"><?= e($t['mainOutlet']) ?></span>
            </div>
            <h1 class="font-headline-lg text-headline-lg font-bold tracking-tight text-on-primary">
                <?= e($greeting) ?>, <?= e($adminName) ?> 👋
            </h1>
            <p class="font-body-md text-body-md text-on-primary-container">
                <?= e($t['heroSub']) ?>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-space-sm shrink-0">
            <button class="inline-flex items-center gap-2 px-space-md py-2.5 rounded-xl bg-surface-container-lowest/15 hover:bg-surface-container-lowest/25 text-on-primary font-label-md text-label-md transition-all backdrop-blur-md" type="button">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span><?= e($t['xlsx']) ?></span>
            </button>
            <button class="inline-flex items-center gap-2 px-space-md py-2.5 rounded-xl bg-surface-container-lowest/15 hover:bg-surface-container-lowest/25 text-on-primary font-label-md text-label-md transition-all backdrop-blur-md" type="button">
                <span class="material-symbols-outlined text-[18px]">inventory</span>
                <span><?= e($t['audit']) ?></span>
            </button>
            <button class="inline-flex items-center gap-2 px-space-md py-2.5 rounded-xl bg-secondary-container hover:bg-secondary text-on-secondary-container hover:text-on-secondary font-label-md text-label-md font-bold transition-all shadow-sm" type="button">
                <span class="material-symbols-outlined text-[18px]">campaign</span>
                <span><?= e($t['campaign']) ?></span>
            </button>
        </div>
    </div>
</section>
<!-- Primary 4 KPI Metric Cards -->
<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
    <!-- Card 1: Total Revenue (current month) -->
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e($t['revenueLabel']) ?></span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight"><?= e($fmtPrice($monthRevenue)) ?></span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-primary-fixed/40 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[22px]">payments</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container">
            <?php if ($revenueChange >= 0): ?>
            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm font-semibold">
                <span class="material-symbols-outlined text-[14px]">trending_up</span>
                <?= e($fmtPct($revenueChange)) ?>
            </div>
            <?php else: ?>
            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold">
                <span class="material-symbols-outlined text-[14px]">trending_down</span>
                <?= e($fmtPct($revenueChange)) ?>
            </div>
            <?php endif; ?>
            <!-- Sparkline Curve SVG -->
            <svg class="w-24 h-7 text-primary-container" fill="none" viewbox="0 0 100 30">
                <path d="<?= e($sparkLine) ?>" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
                <path d="<?= e($sparkArea) ?>" fill="currentColor" opacity="0.12"></path>
            </svg>
            <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['vsLastMonth']) ?></span>
        </div>
    </div>
    <!-- Card 2: Today's Orders -->
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e($t['todayOrders']) ?></span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight"><?= e($fmtNum($todayOrders)) ?> <?= e($t['unit']) !== '' ? e($t['unit']) : '' ?></span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-secondary-fixed/50 flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined text-[22px]">shopping_basket</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container">
            <div class="flex items-center gap-1 font-body-sm text-body-sm text-on-surface-variant">
                <span class="font-semibold text-on-surface"><?= e($t['today']) ?>: <?= e($fmtNum($todayOrders)) ?></span>
                <span class="text-surface-variant">|</span>
                <span class="text-secondary font-medium"><?= e($t['yesterday']) ?>: <?= e($fmtNum($yesterdayOrders)) ?></span>
            </div>
            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full <?= $todayChange >= 0 ? 'bg-primary-fixed text-primary' : 'bg-error-container text-on-error-container' ?> font-label-sm text-label-sm font-semibold">
                <span class="material-symbols-outlined text-[13px]"><?= $todayChange >= 0 ? 'north_east' : 'south_east' ?></span><?= e($fmtPct($todayChange)) ?>
            </span>
        </div>
    </div>
    <!-- Card 3: Average Order Value -->
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e($t['aov']) ?></span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight"><?= e($fmtPrice($aov)) ?></span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-surface-container-high flex items-center justify-center text-primary-container">
                <span class="material-symbols-outlined text-[22px]">receipt_long</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container">
            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm font-semibold">
                <span class="material-symbols-outlined text-[14px]">receipt</span>
                <?= e($fmtNum($totalOrders)) ?> <?= e($t['orders']) ?>
            </div>
            <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['avgAllOrders']) ?></span>
        </div>
    </div>
    <!-- Card 4: Fulfillment Rate -->
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e($t['deliveryRate']) ?></span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight"><?= e($fmtNum($deliveryRate, 1)) ?>%</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-tertiary-fixed/40 flex items-center justify-center text-tertiary-container">
                <span class="material-symbols-outlined text-[22px]">local_shipping</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container">
            <div class="flex items-center gap-2">
                <div class="w-16 h-2 rounded-full bg-surface-container overflow-hidden">
                    <div class="h-full bg-tertiary-container rounded-full" style="width: <?= e(min(100, max(0, $deliveryRate))) ?>%;"></div>
                </div>
                <span class="font-label-sm text-label-sm text-on-surface-variant"><?= e($fmtNum($deliveredCount)) ?> <?= e($t['completed']) ?></span>
            </div>
            <span class="font-label-sm text-label-sm text-primary font-medium"><?= e($fmtPct($cancelPct)) ?> <?= e($t['cancelled']) ?></span>
        </div>
    </div>
</section>
<!-- Interactive Data Visualization & Graphs Section -->
<section class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
    <!-- Left: Revenue & Orders Multi-series Chart (8 Cols) -->
    <div id="report-chart" class="lg:col-span-8 flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-space-md gap-space-sm border-b border-surface-container">
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($t['chartTitle']) ?></h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t[$period === 'daily' ? 'chartSub' : 'chartSub' . ucfirst($period)]) ?></p>
            </div>
            <div class="flex flex-wrap items-center gap-space-xs">
                <div class="inline-flex p-1 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md">
                    <?php foreach (['daily', 'weekly', 'monthly', 'yearly'] as $p): ?>
                    <a href="<?= e(locale_url('/mf-dashboard') . '?period=' . $p) ?>"
                       class="px-3 py-1 rounded-lg transition-colors <?= $period === $p ? 'bg-surface-container-lowest text-primary font-bold shadow-xs' : 'hover:text-on-surface' ?>"><?= e($t[$p]) ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="hidden sm:flex items-center gap-3 pl-2">
                    <div class="flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface">
                        <span class="w-3 h-3 rounded-full bg-primary-container"></span>
                        <span><?= e($t['revenueSeries']) ?></span>
                    </div>
                    <div class="flex items-center gap-1.5 font-label-sm text-label-sm text-secondary">
                        <span class="w-3 h-3 rounded-full bg-secondary-container"></span>
                        <span><?= e($t['ordersSeries']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php foreach (['daily', 'weekly', 'monthly', 'yearly'] as $p): $ch = $charts[$p]; ?>
        <div class="relative w-full mt-space-md pt-space-xs <?= $period === $p ? '' : 'hidden' ?>">
            <div class="absolute -translate-x-1/2 p-2.5 rounded-xl bg-inverse-surface text-inverse-on-surface shadow-xl z-20 pointer-events-none flex flex-col gap-0.5" style="left: <?= e((string)$ch['tooltipLeft']) ?>%; top: 1.5rem;">
                <span class="font-label-sm text-label-sm text-inverse-primary font-semibold"><?= e($ch['labels'][$ch['peakIdx']] ?? '') ?></span>
                <span class="font-headline-sm text-headline-sm font-bold text-surface-lowest"><?= e($fmtPrice($ch['peakRevenue'])) ?></span>
                <div class="flex items-center gap-2 font-body-sm text-body-sm text-surface-variant">
                    <span><?= e($t['ordersColon']) ?> <?= e($fmtNum($ch['peakOrders'])) ?> <?= e($t['unit']) !== '' ? e($t['unit']) : '' ?></span>
                    <span>•</span>
                    <span class="text-tertiary-fixed"><?= e($t['growthColon']) ?> <?= e($fmtPct($ch['peakGrowth'])) ?></span>
                </div>
            </div>
            <div class="w-full h-64 overflow-visible">
                <svg class="w-full h-full" fill="none" preserveaspectratio="none" viewbox="0 0 760 220">
                    <defs>
                        <lineargradient id="revenueGrad_<?= e($p) ?>" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#153e2d" stop-opacity="0.32"></stop>
                            <stop offset="90%" stop-color="#153e2d" stop-opacity="0.01"></stop>
                        </lineargradient>
                        <lineargradient id="orderGrad_<?= e($p) ?>" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#fe932c" stop-opacity="0.25"></stop>
                            <stop offset="90%" stop-color="#fe932c" stop-opacity="0.0"></stop>
                        </lineargradient>
                    </defs>
                    <?php foreach ($ch['gridlines'] as $gl): ?>
                    <line stroke="#f1f3ff" stroke-dasharray="4 4" stroke-width="1.5" x1="30" x2="750" y1="<?= (int)$gl['y'] ?>" y2="<?= (int)$gl['y'] ?>"></line>
                    <?php endforeach; ?>
                    <line stroke="#dce2f7" stroke-width="1.5" x1="30" x2="750" y1="205" y2="205"></line>
                    <?php foreach ($ch['gridlines'] as $i => $gl): ?>
                    <text fill="#717973" font-family="Inter" font-size="10" text-anchor="end" x="22" y="<?= (int)$gl['y'] + 4 ?>"><?= e($ch['gridLabels'][$i] ?? '') ?></text>
                    <?php endforeach; ?>
                    <text fill="#717973" font-family="Inter" font-size="10" text-anchor="end" x="22" y="209">৳0</text>
                    <path d="<?= e($ch['revAreaPath']) ?>" fill="url(#revenueGrad_<?= e($p) ?>)"></path>
                    <path d="<?= e($ch['revLinePath']) ?>" stroke="#153e2d" stroke-linecap="round" stroke-width="3"></path>
                    <path d="<?= e($ch['ordLinePath']) ?>" stroke="#fe932c" stroke-dasharray="3 3" stroke-width="2.5"></path>
                    <circle cx="<?= e((string)$ch['peakRevPt'][0]) ?>" cy="<?= e((string)$ch['peakRevPt'][1]) ?>" fill="#153e2d" r="6" stroke="#ffffff" stroke-width="2.5"></circle>
                    <circle cx="<?= e((string)$ch['peakOrdPt'][0]) ?>" cy="<?= e((string)$ch['peakOrdPt'][1]) ?>" fill="#fe932c" r="4.5" stroke="#ffffff" stroke-width="2"></circle>
                </svg>
            </div>
            <div class="flex justify-between items-center px-6 pt-2 font-label-sm text-label-sm text-on-surface-variant">
                <?php foreach ($ch['labels'] as $i => $lbl): ?>
                <span class="<?= $i === $ch['peakIdx'] ? 'text-primary font-bold' : '' ?>"><?= e($lbl) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="mt-space-md pt-space-sm border-t border-surface-container flex flex-wrap items-center justify-between gap-space-sm font-body-sm text-body-sm">
            <div class="flex items-center gap-space-xs">
                <span class="material-symbols-outlined text-secondary text-[18px]">verified</span>
                <span class="text-on-surface font-semibold"><?= e($t['bestDay']) ?></span>
                <?php foreach (['daily', 'weekly', 'monthly', 'yearly'] as $p): $ch = $charts[$p]; ?>
                <span class="text-on-surface-variant <?= $period === $p ? '' : 'hidden' ?>"><?= e($ch['labels'][$ch['peakIdx']] ?? '') ?> (<?= e($fmtPrice($ch['peakRevenue'])) ?> • <?= e($fmtNum($ch['peakOrders'])) ?> <?= e($t['deliveries']) ?>)</span>
                <?php endforeach; ?>
            </div>
            <div class="flex items-center gap-space-xs">
                <span class="material-symbols-outlined text-tertiary-container text-[18px]">workspace_premium</span>
                <span class="text-on-surface font-semibold"><?= e($t['growthCat']) ?></span>
                <span class="text-primary font-medium"><?= e($growthName !== '' ? $growthName : '—') ?> (<?= e($fmtPct($growthPct)) ?>)</span>
            </div>
        </div>
    </div>
    <!-- Right: Sales Channel & Payment Mix Donut (4 Cols) -->
    <div class="lg:col-span-4 flex flex-col justify-between p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($t['donutTitle']) ?></h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['donutSub']) ?></p>
            </div>
            <button class="p-1 rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-[20px]">more_vert</span>
            </button>
        </div>
        <div class="relative flex items-center justify-center my-4 py-2">
            <svg class="w-60 h-60 -rotate-90 transform" viewbox="0 0 100 100">
                <circle cx="50" cy="50" fill="transparent" r="38" stroke="#f1f3ff" stroke-width="18"></circle>
                <?php foreach ($donutSvgs as $circle): ?>
                <?= $circle ?>
                <?php endforeach; ?>
            </svg>
            <div class="absolute flex flex-col items-center justify-center text-center">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase"><?= e($t['totalTx']) ?></span>
                <span class="font-headline-md text-headline-md font-extrabold text-on-surface leading-tight"><?= e($centerLabel) ?></span>
                <span class="font-label-sm text-label-sm text-primary font-semibold"><?= e($fmtNum($gTotal)) ?> <?= e($t['successful']) ?></span>
            </div>
        </div>
        <div class="flex flex-col gap-2.5">
            <div class="flex items-center justify-between p-2 rounded-xl bg-surface-container-low">
                <div class="flex items-center gap-2.5">
                    <span class="w-3.5 h-3.5 rounded-md bg-primary-container shrink-0"></span>
                    <div class="flex flex-col">
                        <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e($t['cod']) ?></span>
                        <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e($fmtNum($g['cod']['count'])) ?> <?= e($t['parcels']) ?> • <?= e($fmtPrice($g['cod']['total'])) ?></span>
                    </div>
                </div>
                <span class="font-label-lg text-label-lg font-bold text-on-surface"><?= e($fmtNum($codPct)) ?>%</span>
            </div>
            <div class="flex items-center justify-between p-2 rounded-xl bg-surface-container-low">
                <div class="flex items-center gap-2.5">
                    <span class="w-3.5 h-3.5 rounded-md bg-secondary-container shrink-0"></span>
                    <div class="flex flex-col">
                        <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e($t['mfs']) ?></span>
                        <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e($fmtNum($g['mfs']['count'])) ?> <?= e($t['orders']) ?> • <?= e($fmtPrice($g['mfs']['total'])) ?></span>
                    </div>
                </div>
                <span class="font-label-lg text-label-lg font-bold text-on-surface"><?= e($fmtNum($mfsPct)) ?>%</span>
            </div>
            <div class="flex items-center justify-between p-2 rounded-xl bg-surface-container-low">
                <div class="flex items-center gap-2.5">
                    <span class="w-3.5 h-3.5 rounded-md bg-tertiary-fixed-dim shrink-0"></span>
                    <div class="flex flex-col">
                        <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e($t['online']) ?></span>
                        <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e($fmtNum($g['online']['count'])) ?> <?= e($t['orders']) ?> • <?= e($fmtPrice($g['online']['total'])) ?></span>
                    </div>
                </div>
                <span class="font-label-lg text-label-lg font-bold text-on-surface"><?= e($fmtNum($onlinePct)) ?>%</span>
            </div>
        </div>
    </div>
</section>
<!-- Secondary Analytics Row: Top Products & Regional Delivery Mapping -->
<section class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
    <!-- Top Performing Products (7 Cols) -->
    <div class="lg:col-span-7 flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[22px]">eco</span>
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($t['topProducts']) ?></h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['topProductsSub']) ?></p>
                </div>
            </div>
            <a class="font-label-md text-label-md text-secondary font-semibold hover:underline" href="<?= e(locale_url('/mf-dashboard/products')) ?>"><?= e($t['allProducts']) ?></a>
        </div>
        <div class="flex flex-col divide-y divide-surface-container mt-2">
            <?php if (empty($topProducts)): ?>
            <p class="py-6 text-center font-body-sm text-body-sm text-on-surface-variant"><?= e($t['noProducts']) ?></p>
            <?php else: foreach ($topProducts as $idx => $p):
                $name = $productName($p);
                $price = (float)($p['sale_price'] ?? 0) > 0 ? (float)$p['sale_price'] : (float)$p['price'];
                $sold = (int)($p['sold'] ?? 0);
                $stock = (int)($p['stock'] ?? 0);
                $catBadge = $catBadges[(string)($p['category_slug'] ?? '')] ?? null;
                if ($stock <= 15) {
                    $stockState = ['bg-error-container', 'text-on-error-container', $t['lowStock']];
                    $stockClass = 'bg-error-container text-on-error-container';
                } elseif ($stock <= 40) {
                    $stockState = ['bg-secondary-container', 'text-on-secondary-container', $t['reorder']];
                    $stockClass = 'bg-secondary-container text-on-secondary-container';
                } else {
                    $stockState = ['bg-primary-fixed', 'text-on-primary-fixed', $t['inStock']];
                    $stockClass = 'bg-primary-fixed text-on-primary-fixed';
                }
                $categoryName = $isBn
                    ? (string)($p['category_name_bn'] ?? $p['category_name_en'] ?? '')
                    : (string)($p['category_name_en'] ?? $p['category_name_bn'] ?? '');
            ?>
            <div class="py-3 flex items-center justify-between gap-space-sm hover:bg-surface-container-low/50 px-2 rounded-xl transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-surface-container-high overflow-hidden shrink-0">
                        <img class="w-full h-full object-cover" alt="<?= e($name) ?>" src="<?= e(image_url((string)($p['image'] ?? ''), $name, 96)) ?>"/>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="font-label-lg text-label-lg font-semibold text-on-surface"><?= e($name) ?></span>
                            <?php if ($idx === 0): ?>
                            <span class="px-2 py-0.5 rounded-full bg-secondary-fixed text-on-secondary-fixed font-label-sm text-label-sm font-bold"><?= e($t['bestseller']) ?></span>
                            <?php elseif ($catBadge !== null): ?>
                            <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= e($catBadge[1]) ?>"><?= e($catBadge[0]) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e($categoryName) ?><?= !empty($p['sku']) ? ' • ' . e($t['batch']) . e((string)$p['sku']) : '' ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <span class="font-label-lg text-label-lg font-bold text-on-surface"><?= e($fmtPrice($price)) ?></span>
                        <span class="block font-body-sm text-body-sm text-on-surface-variant"><?= e($fmtNum($sold)) ?> <?= e($t['sold']) ?></span>
                    </div>
                    <div class="w-24 text-right">
                        <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= e($stockClass) ?> font-semibold"><?= e($fmtNum($stock)) ?> <?= e($t['left']) ?></span>
                        <span class="block font-body-sm text-body-sm <?= e($stockClass === 'bg-error-container text-on-error-container' ? 'text-on-error-container' : ($stockClass === 'bg-secondary-container text-on-secondary-container' ? 'text-on-secondary-container' : 'text-on-primary-fixed')) ?>"><?= e($stockState[2]) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <!-- Regional Delivery Breakdown (5 Cols) -->
    <div class="lg:col-span-5 flex flex-col justify-between p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($t['regionalTitle']) ?></h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['regionalSub']) ?></p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-primary-fixed/40 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-[20px]">share_location</span>
            </div>
        </div>
        <div class="flex flex-col gap-4 my-2">
            <?php foreach ($regionUI as $rk => [$rLabel, $rDot, $rPctText, $rBar]):
                $rParcels = (int)($regions[$rk]['parcels'] ?? 0);
                $rPct = $regionPct($rParcels);
            ?>
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between font-label-md text-label-md">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full <?= e($rDot) ?>"></span>
                        <span class="text-on-surface font-semibold"><?= e($rLabel) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-on-surface-variant font-body-sm"><?= e($fmtNum($rParcels)) ?> <?= e($t['parcel']) ?></span>
                        <span class="<?= e($rPctText) ?> font-bold"><?= e($fmtNum($rPct)) ?>%</span>
                    </div>
                </div>
                <div class="w-full h-2.5 rounded-full bg-surface-container overflow-hidden">
                    <div class="h-full <?= e($rBar) ?> rounded-full" style="width: <?= e((string)$rPct) ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="p-3.5 rounded-xl bg-surface-container-low flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-[24px]">local_shipping</span>
                <div class="flex flex-col">
                    <span class="font-label-sm text-label-sm text-on-surface-variant uppercase"><?= e($t['turnaround']) ?></span>
                    <span class="font-label-md text-label-md text-on-surface font-bold"><?= e($fmtNum($regionDelivered)) ?> <?= e($t['completed']) ?> • <?= e($fmtNum($regionTotal)) ?> <?= e($t['parcel']) ?></span>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-primary text-on-primary font-label-sm text-label-sm font-semibold"><?= e($fmtNum($deliveryRate, 1)) ?>%</span>
        </div>
    </div>
</section>
<!-- Storefront Search Analytics: Most Searched Terms -->
<section class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
    <!-- Top Searched Terms (10 Cols) -->
    <div class="lg:col-span-10 flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[22px]">search</span>
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($t['searchTitle']) ?></h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['searchSub']) ?></p>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-space-sm mt-3">
            <?php if (empty($searchStats)): ?>
                <p class="col-span-full py-6 text-center font-body-sm text-body-sm text-on-surface-variant"><?= e($t['searchEmpty']) ?></p>
            <?php else: foreach ($searchStats as $idx => $row):
                $term = trim((string)($row['query'] ?? ''));
                $searches = (int)($row['searches'] ?? 0);
                $rank = ($searchPage - 1) * $searchPerPage + $idx + 1;
                $termUrl = locale_url('/products') . '?q=' . rawurlencode($term);
            ?>
            <a href="<?= e($termUrl) ?>" target="_blank" rel="noopener" title="<?= e($term) ?> — <?= e($fmtNum($searches)) ?>"
               class="flex items-center justify-between gap-2 px-space-xs py-2 rounded-xl bg-surface-container-low hover:bg-surface-container transition-colors group">
                <span class="flex items-center gap-2 min-w-0">
                    <span class="w-6 h-6 shrink-0 rounded-lg bg-surface-container-high inline-flex items-center justify-center font-label-sm text-label-sm text-on-surface-variant font-bold"><?= e($fmtNum($rank)) ?></span>
                    <span class="truncate font-label-md text-label-md font-semibold text-on-surface group-hover:text-primary transition-colors"><?= e($term) ?></span>
                </span>
                <span class="shrink-0 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm font-bold"><?= e($fmtNum($searches)) ?></span>
            </a>
            <?php endforeach; endif; ?>
        </div>
        <?php if (!empty($searchStats) && $searchPages > 1): ?>
        <div class="flex flex-wrap items-center justify-between gap-2 pt-space-sm mt-3 border-t border-surface-container">
            <span class="font-label-sm text-label-sm text-on-surface-variant"><?= e($fmtNum(($searchPage - 1) * $searchPerPage + 1)) ?>–<?= e($fmtNum(min($searchPage * $searchPerPage, $searchTermsTotal))) ?> / <?= e($fmtNum($searchTermsTotal)) ?></span>
            <div class="flex items-center gap-1">
                <?php if ($searchPage > 1): ?>
                <a href="<?= e($searchPageUrl($searchPage - 1)) ?>" aria-label="Previous page"
                   class="w-7 h-7 inline-flex items-center justify-center rounded-lg font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-high">&lsaquo;</a>
                <?php endif; ?>
                <?php foreach ($searchNums as $n): ?>
                    <?php if ($n === '…'): ?>
                    <span class="px-1 font-label-md text-label-md text-on-surface-variant">…</span>
                    <?php else: ?>
                    <a href="<?= e($searchPageUrl((int)$n)) ?>"
                       class="w-7 h-7 inline-flex items-center justify-center rounded-lg font-label-md text-label-md <?= ((int)$n === $searchPage ? 'bg-primary text-on-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container-high') ?>"><?= e($fmtNum((int)$n)) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($searchPage < $searchPages): ?>
                <a href="<?= e($searchPageUrl($searchPage + 1)) ?>" aria-label="Next page"
                   class="w-7 h-7 inline-flex items-center justify-center rounded-lg font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-high">&rsaquo;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <!-- Search Overview (2 Cols, ultra-compact) -->
    <div class="lg:col-span-2 flex flex-col p-space-md rounded-2xl bg-surface-container-lowest shadow-sm">
        <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider truncate"><?= e($t['searchOverview']) ?></span>
        <div class="flex items-center justify-between gap-1 mt-1">
            <span class="font-metric-stat text-metric-stat text-primary font-extrabold tracking-tight leading-none min-w-0"><?= e($fmtNum($searchCount30)) ?></span>
            <span class="w-8 h-8 shrink-0 rounded-lg bg-primary-fixed/40 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[18px]">insights</span>
            </span>
        </div>
        <span class="font-label-sm text-label-sm text-on-surface-variant mt-1 truncate"><?= e($t['searchLast30']) ?></span>
        <div class="mt-3 pt-3 flex flex-col gap-1.5 border-t border-surface-container">
            <div class="flex items-center justify-between gap-1 min-w-0">
                <span class="flex items-center gap-1 font-label-sm text-label-sm text-on-surface-variant min-w-0 truncate">
                    <span class="material-symbols-outlined text-[14px]">today</span><?= e($t['searchToday']) ?>
                </span>
                <span class="font-label-md text-label-md text-on-surface font-bold shrink-0"><?= e($fmtNum($searchCountToday)) ?></span>
            </div>
            <div class="flex items-center justify-between gap-1 min-w-0">
                <span class="flex items-center gap-1 font-label-sm text-label-sm text-on-surface-variant min-w-0 truncate">
                    <span class="material-symbols-outlined text-[14px]">insights</span><?= e($t['searchAvg']) ?>
                </span>
                <span class="font-label-md text-label-md text-on-surface font-bold shrink-0"><?= e($fmtNum(round($searchCount30 / 30, 1), 1)) ?></span>
            </div>
        </div>
    </div>
</section>
<!-- Checkout Funnel: reached checkout vs ordered (unregistered guests first) -->
<section class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
    <?php
    $ck = $checkoutFunnel;
    $ckRate = rtrim(rtrim(number_format((float)$ck['rate'], 1), '0'), '.');
    if ($isBn) {
        $ckRate = $bnDigitsOnly($ckRate);
    }
    $ckRate .= '%';
    $ckMax = max(1, (int)$ck['visits']);
    $ckSteps = [
        [$t['ckReached'], (int)$ck['visits'], (int)$ck['guests'], 'flag_circle'],
        [$t['ckOrdered'], (int)$ck['ordered'], (int)$ck['ordered_guests'], 'task_alt'],
        [$t['ckLeft'], (int)$ck['abandoned'], (int)$ck['abandoned_guests'], 'directions_walk'],
    ];
    ?>
    <!-- Funnel steps (8 Cols) -->
    <div class="lg:col-span-8 flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[22px]">shopping_cart_checkout</span>
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($t['ckTitle']) ?></h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['ckSub']) ?></p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-primary text-on-primary font-label-sm text-label-sm font-semibold"><?= e($ckRate) ?></span>
        </div>
        <div class="flex flex-col gap-space-sm mt-3">
            <?php foreach ($ckSteps as [$ckLabel, $ckValue, $ckGuestValue, $ckIcon]): ?>
            <div class="flex items-center gap-3 px-space-xs py-2 rounded-xl bg-surface-container-low">
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant shrink-0"><?= e($ckIcon) ?></span>
                <span class="flex-1 min-w-0">
                    <span class="flex items-center justify-between gap-2">
                        <span class="truncate font-label-md text-label-md font-semibold text-on-surface"><?= e($ckLabel) ?></span>
                        <span class="font-label-md text-label-md font-bold text-on-surface"><?= e($fmtNum($ckValue)) ?></span>
                    </span>
                    <span class="block mt-1 h-1.5 rounded-full bg-surface-container-high overflow-hidden">
                        <span class="block h-full rounded-full bg-primary" style="width: <?= e((string)(int)round($ckValue / $ckMax * 100)) ?>%"></span>
                    </span>
                </span>
                <span class="shrink-0 px-2 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm font-bold"><?= e($t['ckGuestsShort']) ?> <?= e($fmtNum($ckGuestValue)) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="mt-3 pt-3 border-t border-surface-container font-label-sm text-label-sm text-on-surface-variant"><?= e($t['ckNote']) ?></p>
    </div>
    <!-- Recent guest visits (4 Cols) -->
    <?php
    // One stable identity per visitor. IPv6 addresses rotate between
    // sessions, so the panel groups on the /64 prefix (visitor_ip_key())
    // and numbers the groups newest-first. The full address stays in the
    // database and in the row tooltip.
    $ckVisitorKeys   = [];
    $ckVisitorLabels = [];
    foreach ((array)($checkoutGuests ?? []) as $ckIdx => $ckGuest) {
        $ckKey = visitor_ip_key((string)($ckGuest['ip'] ?? ''));
        if ($ckKey === '') {
            $ckKey = 'unknown';
        }
        $ckVisitorKeys[$ckIdx] = $ckKey;
        if (!isset($ckVisitorLabels[$ckKey])) {
            $ckVisitorLabels[$ckKey] = count($ckVisitorLabels) + 1;
        }
    }
    $ckUniqueVisitors = count($ckVisitorLabels);
    ?>
    <div class="lg:col-span-4 flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center justify-between gap-2 pb-space-sm border-b border-surface-container">
            <div class="flex items-center gap-2 min-w-0">
                <span class="material-symbols-outlined text-primary text-[22px] shrink-0">person_search</span>
                <div class="min-w-0">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($t['ckRecentGuests']) ?></h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['ckGuestsSub']) ?></p>
                </div>
            </div>
            <?php if ($ckUniqueVisitors > 0): ?>
            <span class="shrink-0 px-2 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm font-bold"><?= e($fmtNum($ckUniqueVisitors)) ?> <?= e($t['ckUniqueGuests']) ?></span>
            <?php endif; ?>
        </div>
        <?php if (empty($checkoutGuests)): ?>
            <p class="py-6 text-center font-body-sm text-body-sm text-on-surface-variant"><?= e($t['ckNoGuests']) ?></p>
        <?php else: ?>
        <div class="flex flex-col divide-y divide-surface-container">
            <?php foreach ($checkoutGuests as $ckIdx => $ckGuest):
                $ckDone = (int)$ckGuest['converted'] === 1;
                // Visitor identity: a short label plus the shortened address
                // (IPv6 /64 prefix). The full address stays in the tooltip.
                $ckIp     = (string)($ckGuest['ip'] ?? '');
                $ckKey    = $ckVisitorKeys[$ckIdx] ?? visitor_ip_key($ckIp);
                $ckName   = $t['ckVisitor'] . ' ' . $fmtNum($ckVisitorLabels[$ckKey] ?? 1);
                // Cart snapshot taken when they reached checkout: [{id,en,bn,qty}].
                $ckItems = json_decode((string)($ckGuest['cart_items'] ?? ''), true);
                $ckLabels = [];
                $ckPlain  = [];
                if (is_array($ckItems)) {
                    foreach ($ckItems as $ckIt) {
                        $ckEn  = trim((string)($ckIt['en'] ?? ''));
                        $ckBn  = trim((string)($ckIt['bn'] ?? ''));
                        $ckNm  = $isBn ? ($ckBn !== '' ? $ckBn : $ckEn) : ($ckEn !== '' ? $ckEn : $ckBn);
                        if ($ckNm === '') {
                            continue;
                        }
                        $ckQty = max(1, (int)($ckIt['qty'] ?? 1));
                        $ckLb  = $ckNm . ($ckQty > 1 ? ' ×' . $fmtNum($ckQty) : '');
                        $ckPlain[] = $ckLb;
                        $ckPid = (int)($ckIt['id'] ?? 0);
                        $ckLabels[] = $ckPid > 0
                            ? '<a href="' . e(locale_url('/mf-dashboard/products/' . $ckPid . '/edit')) . '" class="underline decoration-dotted underline-offset-2 hover:text-primary" title="' . e($ckNm) . '">' . e($ckLb) . '</a>'
                            : e($ckLb);
                    }
                }
                $ckCartTitle = implode(', ', $ckPlain);
                ?>
            <div class="flex items-center justify-between gap-2 py-2" title="<?= e($ckIp . ' · ' . (string)$ckGuest['referrer']) ?>">
                <span class="flex flex-col min-w-0">
                    <span class="font-label-md text-label-md font-semibold text-on-surface truncate"><?= e($ckName) ?><span class="font-label-sm text-label-sm font-normal text-on-surface-variant"> · <?= e($ckKey) ?></span></span>
                    <span class="font-label-sm text-label-sm text-on-surface-variant truncate"><?= e($fmtDate((string)$ckGuest['created_at'])) ?> · <?= e($fmtPrice((float)$ckGuest['cart_value'])) ?></span>
                    <?php if ($ckLabels !== []): ?><span class="font-label-sm text-label-sm text-on-surface-variant truncate" title="<?= e($ckCartTitle) ?>"><?= e($t['ckInCart']) ?>: <?= implode(', ', $ckLabels) ?></span><?php endif; ?>
                </span>
                <span class="shrink-0 px-2 py-0.5 rounded-full font-label-sm text-label-sm font-bold <?= $ckDone ? 'bg-primary text-on-primary' : 'bg-error-container text-on-error-container' ?>"><?= e($ckDone ? $t['ckDidOrder'] : $t['ckNoOrder']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<!-- Live Orders Management Ledger Table -->
<section class="flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-space-md gap-space-md border-b border-surface-container">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($t['recentTitle']) ?></h2>
                <span class="px-2.5 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm font-bold"><?= e($fmtNum($pendingCount)) ?><?= e($t['newProcessing']) ?></span>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['recentSub']) ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a class="px-3 py-1.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md font-semibold shadow-xs" href="<?= e(locale_url('/mf-dashboard/orders')) ?>"><?= e($t['allOrders']) ?></a>
            <a class="px-3 py-1.5 rounded-xl bg-surface-container-low text-on-surface-variant hover:text-on-surface font-label-md text-label-md transition-colors" href="<?= e(locale_url('/mf-dashboard/orders?status=pending')) ?>"><?= e($t['pending']) ?> (<?= e($fmtNum($statusCounts['pending'] ?? 0)) ?>)</a>
            <a class="px-3 py-1.5 rounded-xl bg-surface-container-low text-on-surface-variant hover:text-on-surface font-label-md text-label-md transition-colors" href="<?= e(locale_url('/mf-dashboard/orders?status=processing')) ?>"><?= e($t['processing']) ?> (<?= e($fmtNum($statusCounts['processing'] ?? 0)) ?>)</a>
            <a class="px-3 py-1.5 rounded-xl bg-surface-container-low text-on-surface-variant hover:text-on-surface font-label-md text-label-md transition-colors" href="<?= e(locale_url('/mf-dashboard/orders?status=shipped')) ?>"><?= e($t['inTransit']) ?> (<?= e($fmtNum($statusCounts['shipped'] ?? 0)) ?>)</a>
            <a class="px-3 py-1.5 rounded-xl bg-surface-container-low text-on-surface-variant hover:text-on-surface font-label-md text-label-md transition-colors" href="<?= e(locale_url('/mf-dashboard/orders?status=delivered')) ?>"><?= e($t['delivered']) ?></a>
        </div>
    </div>
    <div class="w-full overflow-x-auto mt-space-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                    <th class="py-3 px-4 font-semibold"><?= e($t['thOrderId']) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e($t['thCustomer']) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e($t['thItems']) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e($t['thAmount']) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e($t['thMethod']) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e($t['thStatus']) ?></th>
                    <th class="py-3 px-4 font-semibold text-right"><?= e($t['thAction']) ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container font-body-md text-body-md">
                <?php if (empty($recentOrders)): ?>
                <tr>
                    <td class="py-8 text-center text-on-surface-variant" colspan="7"><?= e($t['noOrders']) ?></td>
                </tr>
                <?php else: foreach ($recentOrders as $order):
                    $status = (string)($order['status'] ?? 'pending');
                    $sUI = $statusUI[$status] ?? [$t['statusOther'], 'bg-surface-container-high text-on-surface'];
                    $method = strtolower((string)($order['payment_method'] ?? 'cod'));
                    $pUI = $payUI[$method] ?? [$t['payOther'], 'bg-surface-container-high text-on-surface font-medium', 'credit_card'];
                    $orderUrl = locale_url('/mf-dashboard/orders/' . (int)$order['id']);
                    $customer = customer_display_name((string)($order['shipping_name'] ?? ''), $order['shipping_email'] ?? null, app_locale());
                    $addressParts = array_filter([
                        (string)($order['shipping_address'] ?? ''),
                        (string)($order['shipping_city'] ?? ''),
                    ]);
                ?>
                <tr class="hover:bg-surface-container-low/40 transition-colors">
                    <td class="py-3.5 px-4 font-label-md text-label-md font-bold text-primary">#<?= e((string)$order['order_number']) ?></td>
                    <td class="py-3.5 px-4">
                        <div class="flex flex-col">
                            <span class="font-semibold text-on-surface"><?= e($customer) ?></span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e(implode(', ', $addressParts)) ?></span>
                        </div>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="text-on-surface"><?= e((string)($order['items_summary'] ?? '—')) ?></span>
                    </td>
                    <td class="py-3.5 px-4 font-label-lg text-label-lg font-bold text-on-surface"><?= e($fmtPrice((float)($order['total'] ?? 0))) ?></td>
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md font-label-sm text-label-sm <?= e($pUI[1]) ?>">
                            <span class="material-symbols-outlined text-[14px]"><?= e($pUI[2]) ?></span>
                            <?= e($pUI[0]) ?>
                        </span>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-label-sm text-label-sm font-bold <?= e($sUI[1]) ?>">
                            <?php if ($status === 'processing' || $status === 'pending'): ?>
                            <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"></span>
                            <?php else: ?>
                            <span class="material-symbols-outlined text-[14px]"><?= $status === 'delivered' ? 'done_all' : ($status === 'shipped' ? 'local_shipping' : 'close') ?></span>
                            <?php endif; ?>
                            <?= e($sUI[0]) ?>
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-right">
                        <div class="inline-flex items-center gap-1">
                            <a href="<?= e($orderUrl) ?>" class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="<?= e($t['viewInvoice']) ?>">
                                <span class="material-symbols-outlined text-[18px]">receipt</span>
                            </a>
                            <a href="<?= e($orderUrl) ?>" class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="<?= e($t['details']) ?>">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="flex flex-col sm:flex-row items-center justify-between pt-space-md mt-space-xs border-t border-surface-container gap-space-sm">
        <span class="font-body-sm text-body-sm text-on-surface-variant">
            <?php if ($isBn): ?>
            মোট <?= e($fmtNum($totalOrders)) ?>টি অর্ডারের মধ্যে <?= e($fmtNum(count($recentOrders))) ?>টি দেখানো হচ্ছে
            <?php else: ?>
            Showing <?= e($fmtNum(count($recentOrders))) ?> of <?= e($fmtNum($totalOrders)) ?> orders
            <?php endif; ?>
        </span>
        <div class="flex items-center gap-1">
            <span class="px-3 py-1.5 rounded-lg bg-surface-container-low text-on-surface/50 font-label-md text-label-md cursor-not-allowed"><?= e($t['prevPage']) ?></span>
            <span class="w-8 h-8 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold flex items-center justify-center">1</span>
            <a class="px-3 py-1.5 rounded-lg bg-surface-container-low text-on-surface-variant font-label-md text-label-md hover:bg-surface-container transition-colors" href="<?= e(locale_url('/mf-dashboard/orders')) ?>"><?= e($t['nextPage']) ?></a>
        </div>
    </div>
</section>
</div>