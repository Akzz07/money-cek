<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\Command\TransactionCommandParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VoiceCommandController extends Controller
{
    /**
     * Parse natural language voice text into a structured financial transaction.
     */
    public function parse(Request $request, TransactionCommandParser $parser): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $result = $parser->parse($user, (string) $validated['text']);

        $accounts = $user->accounts()->active()->orderBy('name')->get(['id', 'name', 'type']);
        $categories = Category::forUser($user)->active()->orderBy('name')->get(['id', 'name', 'type']);

        return response()->json([
            'status' => 'success',
            'data' => $result->toArray(),
            'meta' => [
                'accounts' => $accounts,
                'categories' => $categories,
            ],
        ]);
    }
}
