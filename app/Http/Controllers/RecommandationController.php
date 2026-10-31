<?php

namespace App\Http\Controllers;

use App\Models\Echange;
use App\Models\Communaute;
use App\Models\User;
use Illuminate\Http\Request;

class RecommandationController extends Controller
{
    public function index()
    {
        return view('recommandations.index', [
            'communautes' => Communaute::orderBy('nom')->get(),
            'recommendations' => null,
            'criteria' => [],
        ]);
    }

    public function recommend(Request $request)
    {
        $criteria = $request->validate([
            'role' => 'required|in:acheteur,vendeur',
            'communaute_id' => 'required|exists:communautes,id',
            'quantite_kwh' => 'required|numeric|min:0.01',
            'prix_unitaire' => 'required|numeric|min:0.0001',
        ]);

        $candidateRole = $criteria['role'] === 'acheteur' ? 'vendeur' : 'acheteur';
        $counterpartyColumn = $candidateRole . '_id';

        $history = Echange::query()
            ->where('communaute_id', $criteria['communaute_id'])
            ->whereIn('statut', ['accepte', 'termine'])
            ->get();

        $recommendations = $history
            ->groupBy($counterpartyColumn)
            ->map(function ($echanges, $userId) use ($criteria, $candidateRole) {
                $averageQuantity = (float) $echanges->avg('quantite_kwh');
                $averagePrice = (float) $echanges->avg('prix_unitaire');
                $quantityFit = max(0, 1 - abs($averageQuantity - $criteria['quantite_kwh']) / max((float) $criteria['quantite_kwh'], 0.01));
                $priceFit = max(0, 1 - abs($averagePrice - $criteria['prix_unitaire']) / max((float) $criteria['prix_unitaire'], 0.0001));
                $completed = $echanges->where('statut', 'termine')->count();
                $experience = min(1, $echanges->count() / 5);
                $completion = $echanges->count() > 0 ? $completed / $echanges->count() : 0;
                $score = (int) round(($priceFit * 40) + ($quantityFit * 30) + ($experience * 20) + ($completion * 10));

                return [
                    'user_id' => (int) $userId,
                    'score' => $score,
                    'average_quantity' => round($averageQuantity, 2),
                    'average_price' => round($averagePrice, 4),
                    'completed_count' => $completed,
                    'transaction_count' => $echanges->count(),
                ];
            })
            ->sortByDesc('score')
            ->values();

        $usersById = User::whereIn('id', $recommendations->pluck('user_id'))->get()->keyBy('id');
        $recommendations = $recommendations
            ->filter(fn ($recommendation) => $usersById->has($recommendation['user_id']))
            ->map(function ($recommendation) use ($usersById, $candidateRole) {
                $recommendation['user'] = $usersById->get($recommendation['user_id']);
                $recommendation['role'] = $candidateRole;

                return $recommendation;
            })
            ->values();

        return view('recommandations.index', [
            'communautes' => Communaute::orderBy('nom')->get(),
            'recommendations' => $recommendations,
            'criteria' => $criteria,
        ]);
    }
}
