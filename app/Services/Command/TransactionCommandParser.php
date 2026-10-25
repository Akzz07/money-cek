<?php

declare(strict_types=1);

namespace App\Services\Command;

use App\Domain\Money\Money;
use App\DTOs\ParsedCommandResult;
use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class TransactionCommandParser
{
    /**
     * Parse natural language command into a structured financial transaction DTO.
     */
    public function parse(User $user, string $text): ParsedCommandResult
    {
        $rawText = trim($text);
        $normalizedText = mb_strtolower($rawText);

        $timezone = $user->timezone ?? 'Asia/Jakarta';

        // 1. Detect Intent / Transaction Type
        $type = $this->detectType($normalizedText);

        // 2. Extract Amount
        $matchedAmountToken = '';
        $amount = $this->extractAmount($normalizedText, $matchedAmountToken);

        // 3. Extract Date
        $matchedDateToken = '';
        $transactionDate = $this->extractDate($normalizedText, $timezone, $matchedDateToken);

        // 4. Match Account
        $matchedAccountToken = '';
        $account = $this->matchAccount($user, $normalizedText, $matchedAccountToken);

        // 5. Match Category
        $matchedCategoryToken = '';
        $isExplicitCategory = false;
        $category = $this->matchCategory($user, $type, $normalizedText, $matchedCategoryToken, $isExplicitCategory);

        // 6. Clean and Extract Description
        $tokensToStrip = array_filter([
            $matchedAmountToken,
            $matchedDateToken,
            $matchedAccountToken,
            $isExplicitCategory ? $matchedCategoryToken : null,
        ]);
        $description = $this->cleanDescription($rawText, $tokensToStrip, $type, $category?->name);

        // 7. Calculate Confidence & Missing Fields
        $missingFields = [];
        $confidence = 1.0;

        if ($amount === null) {
            $missingFields[] = 'amount';
            $confidence -= 0.4;
        }

        if ($account === null) {
            $missingFields[] = 'source_account_id';
            $confidence -= 0.25;
        } elseif ($matchedAccountToken === '') {
            // Account was defaulted rather than explicitly spoken
            $confidence -= 0.1;
        }

        if ($category === null) {
            $missingFields[] = 'category_id';
            $confidence -= 0.25;
        } elseif ($matchedCategoryToken === '') {
            // Category was defaulted
            $confidence -= 0.05;
        }

        $confidence = max(0.1, min(1.0, round($confidence, 2)));

        return new ParsedCommandResult(
            type: $type,
            amount: $amount,
            accountId: $account?->id,
            accountName: $account?->name,
            categoryId: $category?->id,
            categoryName: $category?->name,
            description: $description,
            transactionDate: $transactionDate,
            confidence: $confidence,
            rawText: $rawText,
            missingFields: $missingFields,
            clientRequestId: (string) Str::uuid(),
        );
    }

    /**
     * Detect whether the command represents an income or an expense.
     */
    private function detectType(string $text): TransactionType
    {
        $incomeKeywords = [
            'gaji', 'salary', 'payroll', 'upah', 'pemasukan', 'terima', 'dapat', 'masuk',
            'cair', 'bonus', 'freelance', 'penjualan', 'untung', 'dividen', 'cashback',
            'transfer dari', 'dikasih', 'diberi', 'hibah', 'klaim', 'reimburse',
        ];

        foreach ($incomeKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return TransactionType::Income;
            }
        }

        return TransactionType::Expense;
    }

    /**
     * Extract nominal amount from Indonesian text patterns.
     */
    private function extractAmount(string $text, string &$matchedToken): ?Money
    {
        // Pattern 1: Millions ("1.5 juta", "1,5jt", "2 juta", "setengah juta")
        if (preg_match('/\b(setengah)\s*(?:juta|jt)\b/iu', $text, $matches)) {
            $matchedToken = $matches[0];

            return Money::of(500000);
        }

        if (preg_match('/\b(\d+(?:[.,]\d+)?)\s*(?:juta|jt)\b/iu', $text, $matches)) {
            $matchedToken = $matches[0];
            $val = (float) str_replace(',', '.', $matches[1]);
            $nominal = (int) round($val * 1000000);

            return Money::of($nominal);
        }

        // Pattern 2: Thousands with shorthand ("25rb", "25 ribu", "25k", "2.5k")
        if (preg_match('/\b(setengah)\s*(?:ribu|rb)\b/iu', $text, $matches)) {
            $matchedToken = $matches[0];

            return Money::of(500);
        }

        if (preg_match('/\b(\d+(?:[.,]\d+)?)\s*(?:ribu|rb|k)\b/iu', $text, $matches)) {
            $matchedToken = $matches[0];
            $val = (float) str_replace(',', '.', $matches[1]);
            $nominal = (int) round($val * 1000);

            return Money::of($nominal);
        }

        // Pattern 3: Word-based numbers in Indonesian
        $wordMap = [
            'satu juta' => 1000000,
            'dua juta' => 2000000,
            'tiga juta' => 3000000,
            'empat juta' => 4000000,
            'lima juta' => 5000000,
            'sepuluh juta' => 10000000,
            'seratus ribu' => 100000,
            'dua ratus ribu' => 200000,
            'tiga ratus ribu' => 300000,
            'empat ratus ribu' => 400000,
            'lima ratus ribu' => 500000,
            'sepuluh ribu' => 10000,
            'dua puluh lima ribu' => 25000,
            'dua puluh ribu' => 20000,
            'tiga puluh lima ribu' => 35000,
            'tiga puluh ribu' => 30000,
            'empat puluh ribu' => 40000,
            'lima puluh ribu' => 50000,
            'tujuh puluh lima ribu' => 75000,
        ];

        foreach ($wordMap as $phrase => $amountVal) {
            if (str_contains($text, $phrase)) {
                $matchedToken = $phrase;

                return Money::of($amountVal);
            }
        }

        // Pattern 4: Standard formatted currency numbers ("Rp 50.000", "50.000", "50000")
        if (preg_match('/(?:rp\.?\s*)?(\d{1,3}(?:\.\d{3})+(?:,\d+)?|\d{4,})(?!\s*(?:juta|jt|ribu|rb|k))/iu', $text, $matches)) {
            $matchedToken = $matches[0];
            $cleanNum = str_replace('.', '', $matches[1]);
            $cleanNum = str_replace(',', '.', $cleanNum);
            $val = (float) $cleanNum;
            if ($val > 0) {
                return Money::of((int) round($val));
            }
        }

        return null;
    }

    /**
     * Extract date from temporal cues in Indonesian text.
     */
    private function extractDate(string $text, string $timezone, string &$matchedDateToken): CarbonImmutable
    {
        $now = CarbonImmutable::now($timezone);

        if (preg_match('/\b(2\s*hari\s*lalu|kemarin\s*dulu)\b/iu', $text, $matches)) {
            $matchedDateToken = $matches[0];

            return $now->subDays(2)->startOfDay();
        }

        if (preg_match('/\b(kemarin|tadi\s*malam)\b/iu', $text, $matches)) {
            $matchedDateToken = $matches[0];

            return $now->subDay()->startOfDay();
        }

        if (preg_match('/\b(besok)\b/iu', $text, $matches)) {
            $matchedDateToken = $matches[0];

            return $now->addDay()->startOfDay();
        }

        if (preg_match('/\b(hari\s*ini|tadi\s*pagi|tadi\s*siang|tadi\s*sore|barusan|tadi)\b/iu', $text, $matches)) {
            $matchedDateToken = $matches[0];

            return $now->startOfDay();
        }

        return $now->startOfDay();
    }

    /**
     * Match the source account from the user's active accounts.
     */
    private function matchAccount(User $user, string $text, string &$matchedAccountToken): ?Account
    {
        $accounts = $user->accounts()->active()->get();

        if ($accounts->isEmpty()) {
            return null;
        }

        // 1. Exact or partial match with account name or institution name
        foreach ($accounts as $account) {
            $accName = mb_strtolower($account->name);
            $instName = $account->institution_name ? mb_strtolower($account->institution_name) : null;

            if (str_contains($text, $accName)) {
                $matchedAccountToken = $account->name;

                return $account;
            }

            if ($instName !== null && str_contains($text, $instName)) {
                $matchedAccountToken = $account->institution_name;

                return $account;
            }
        }

        // 2. Synonyms for account types and popular institutions
        $synonymMap = [
            'tunai' => AccountType::Cash,
            'cash' => AccountType::Cash,
            'dompet' => AccountType::Cash,
            'uang fisik' => AccountType::Cash,
            'bca' => 'bca',
            'mandiri' => 'mandiri',
            'bri' => 'bri',
            'bni' => 'bni',
            'jago' => 'jago',
            'seabank' => 'seabank',
            'gopay' => 'gopay',
            'ovo' => 'ovo',
            'dana' => 'dana',
            'shopeepay' => 'shopee',
        ];

        foreach ($synonymMap as $cue => $target) {
            if (preg_match('/\b'.preg_quote($cue, '/').'\b/iu', $text)) {
                // If target is AccountType enum
                if ($target instanceof AccountType) {
                    $found = $accounts->first(fn (Account $acc) => $acc->type === $target);
                    if ($found) {
                        $matchedAccountToken = $cue;

                        return $found;
                    }
                } else {
                    // Match by string substring in name or institution
                    $found = $accounts->first(function (Account $acc) use ($target) {
                        return str_contains(mb_strtolower($acc->name), (string) $target)
                            || str_contains(mb_strtolower((string) $acc->institution_name), (string) $target);
                    });
                    if ($found) {
                        $matchedAccountToken = $cue;

                        return $found;
                    }
                }
            }
        }

        // 3. Fallback to user's first active account if not explicitly found
        $defaultAccount = $accounts->first();
        if ($defaultAccount) {
            $matchedAccountToken = ''; // Mark token empty to indicate fallback

            return $defaultAccount;
        }

        return null;
    }

    /**
     * Match transaction category from the user's active categories using keyword taxonomy.
     */
    private function matchCategory(User $user, TransactionType $type, string $text, string &$matchedCategoryToken, bool &$isExplicitCategory = false): ?Category
    {
        $isExplicitCategory = false;
        $categoryType = $type === TransactionType::Expense ? CategoryType::Expense : CategoryType::Income;
        $categories = Category::forUser($user, $categoryType)->active()->get();

        if ($categories->isEmpty()) {
            return null;
        }

        // 1. Direct explicit category mention (e.g. "kategori Makanan" or "kategori: Makanan")
        foreach ($categories as $cat) {
            $catName = mb_strtolower($cat->name);
            if (preg_match('/(?:kategori\s*:?\s*)('.preg_quote($catName, '/').')\b/iu', $text, $m)) {
                $matchedCategoryToken = $m[0];
                $isExplicitCategory = true;

                return $cat;
            }
        }

        // 2. Keyword Taxonomy Map
        $taxonomy = [
            'Makanan' => [
                'kopi', 'makan', 'sarapan', 'siang', 'malam', 'warteg', 'cafe', 'kafe', 'resto',
                'restoran', 'snack', 'jajan', 'bakso', 'mie', 'nasi', 'ayam', 'gofood', 'grabfood',
                'shopeefood', 'minum', 'jus', 'susu', 'roti', 'kfc', 'mcd', 'starbucks', 'padang',
            ],
            'Transportasi' => [
                'bensin', 'pertalite', 'pertamax', 'solar', 'bbm', 'spbu', 'parkir', 'tol',
                'ojol', 'gojek', 'grab', 'maxim', 'krl', 'mrt', 'lrt', 'busway', 'transjakarta',
                'taksi', 'tiket', 'pesawat', 'kereta', 'bengkel', 'tambal', 'cuci motor', 'cuci mobil',
            ],
            'Tagihan' => [
                'listrik', 'pln', 'air', 'pdam', 'wifi', 'internet', 'indihome', 'biznet', 'firstmedia',
                'pulsa', 'kuota', 'kontrakan', 'kos', 'sewa', 'bpjs', 'netflix', 'spotify', 'iuran', 'tagihan',
            ],
            'Belanja' => [
                'belanja', 'baju', 'celana', 'sepatu', 'tokopedia', 'shopee', 'lazada', 'tiktok shop',
                'indomaret', 'alfamart', 'hypermart', 'supermarket', 'mall', 'skincare', 'makeup', 'parfum',
            ],
            'Hiburan' => [
                'nonton', 'bioskop', 'cinema', 'xxi', 'game', 'steam', 'liburan', 'hotel', 'staycation', 'karaoke',
            ],
            'Kesehatan' => [
                'obat', 'apotek', 'dokter', 'rumah sakit', 'klinik', 'vitamin', 'medis', 'vaksin',
            ],
            'Pendidikan' => [
                'buku', 'kursus', 'les', 'spp', 'kuliah', 'seminar', 'pelatihan',
            ],
            'Gaji' => [
                'gaji', 'salary', 'payroll', 'upah',
            ],
            'Freelance' => [
                'freelance', 'proyek', 'klien', 'side job', 'part time', 'desain', 'coding',
            ],
            'Bisnis' => [
                'bisnis', 'penjualan', 'dagang', 'omset', 'toko', 'untung',
            ],
            'Hadiah' => [
                'hadiah', 'bonus', 'thr', 'cashback', 'giveaway', 'angpau',
            ],
            'Bunga/Dividen' => [
                'bunga', 'dividen', 'reksadana', 'saham', 'crypto', 'investasi', 'yield', 'sukuk', 'obligasi',
            ],
        ];

        foreach ($taxonomy as $targetCategoryName => $cues) {
            foreach ($cues as $cue) {
                if (preg_match('/\b'.preg_quote($cue, '/').'\b/iu', $text)) {
                    // Find user's category matching this name or substring
                    $found = $categories->first(function (Category $cat) use ($targetCategoryName) {
                        return strcasecmp($cat->name, $targetCategoryName) === 0
                            || str_contains(mb_strtolower($cat->name), mb_strtolower($targetCategoryName));
                    });

                    if ($found) {
                        $matchedCategoryToken = $cue;
                        $isExplicitCategory = false;

                        return $found;
                    }
                }
            }
        }

        // 3. Direct category name standalone match
        foreach ($categories as $cat) {
            $catName = mb_strtolower($cat->name);
            if (preg_match('/\b'.preg_quote($catName, '/').'\b/iu', $text)) {
                $matchedCategoryToken = $cat->name;
                $isExplicitCategory = true;

                return $cat;
            }
        }

        // 4. Fallback to first category or 'Lainnya'
        $lainnya = $categories->first(fn (Category $c) => strcasecmp($c->name, 'Lainnya') === 0);
        if ($lainnya) {
            $matchedCategoryToken = '';

            return $lainnya;
        }

        $firstCat = $categories->first();
        if ($firstCat) {
            $matchedCategoryToken = '';

            return $firstCat;
        }

        return null;
    }

    /**
     * Clean up input text to isolate a concise and descriptive transaction title.
     *
     * @param  array<string>  $tokensToStrip
     */
    private function cleanDescription(string $rawText, array $tokensToStrip, TransactionType $type, ?string $categoryName): string
    {
        $clean = $rawText;

        // Strip matched entity tokens
        foreach ($tokensToStrip as $token) {
            if ($token !== '') {
                $clean = preg_replace('/\b'.preg_quote($token, '/').'\b/iu', ' ', (string) $clean);
            }
        }

        // Strip filler / preposition words
        $fillerWords = [
            'beli', 'bayar', 'bayarin', 'keluar', 'pengeluaran', 'terima', 'dapat', 'masuk',
            'pemasukan', 'transfer', 'ke', 'dari', 'pakai', 'pake', 'menggunakan', 'lewat',
            'sebesar', 'seharga', 'senilai', 'untuk', 'buat', 'kategori', 'akun', 'rekening',
            'hari ini', 'kemarin', 'tadi pagi', 'tadi siang', 'tadi malam', 'tadi sore', 'barusan',
            'dong', 'tolong', 'catat', 'catatkan', 'masukin', 'input',
        ];

        foreach ($fillerWords as $filler) {
            $clean = preg_replace('/\b'.preg_quote($filler, '/').'\b/iu', ' ', (string) $clean);
        }

        // Remove residual numbers, Rp symbols, and punctuation
        $clean = preg_replace('/(?:rp\.?\s*)?\d+(?:[.,]\d+)?/iu', ' ', (string) $clean);
        $clean = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', (string) $clean);
        $clean = trim((string) preg_replace('/\s+/', ' ', (string) $clean));

        if (mb_strlen($clean) >= 2) {
            return Str::ucfirst($clean);
        }

        // Fallback description if all words were stripped
        if ($categoryName) {
            return Str::ucfirst($categoryName);
        }

        return $type === TransactionType::Income ? 'Pemasukan' : 'Pengeluaran';
    }
}
