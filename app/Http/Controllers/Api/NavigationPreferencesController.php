<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NavigationPreferencesController extends Controller
{
    /**
     * Get the current user's navigation preferences.
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'navigation' => $user->getNavigationPreferences()
        ]);
    }

    /**
     * Update the current user's navigation preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'collapsed_groups' => 'sometimes|array',
            'collapsed_groups.*' => 'boolean',
            'all_collapsed' => 'sometimes|boolean',
        ]);

        $navPrefs = $user->getNavigationPreferences();
        
        if ($request->has('collapsed_groups')) {
            $navPrefs['collapsed_groups'] = array_merge(
                $navPrefs['collapsed_groups'] ?? [],
                $request->input('collapsed_groups')
            );
        }

        if ($request->has('all_collapsed')) {
            $navPrefs['all_collapsed'] = $request->input('all_collapsed');
        }

        $user->updateNavigationPreferences($navPrefs);

        return response()->json([
            'success' => true,
            'navigation' => $navPrefs
        ]);
    }

    /**
     * Toggle a specific navigation group.
     */
    public function toggleGroup(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'group' => 'required|string',
            'collapsed' => 'required|boolean',
        ]);

        $group = $request->input('group');
        $collapsed = $request->input('collapsed');
        
        $user->setNavigationGroupCollapsed($group, $collapsed);

        return response()->json([
            'success' => true,
            'group' => $group,
            'collapsed' => $collapsed,
            'navigation' => $user->getNavigationPreferences()
        ]);
    }

    /**
     * Toggle all navigation groups at once.
     */
    public function toggleAll(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'collapsed' => 'required|boolean',
        ]);

        $collapsed = $request->input('collapsed');
        $navPrefs = $user->getNavigationPreferences();
        
        // Get all navigation groups from the config
        $groups = [
            'Dashboard & Overview',
            'Production Management', 
            'Seed Management',
            'Inventory & Materials',
            'Sales & Products',
            'Order Management',
            'Analytics & Reports',
            'System & Settings',
        ];

        $collapsedGroups = [];
        foreach ($groups as $group) {
            $collapsedGroups[$group] = $collapsed;
        }

        $navPrefs['collapsed_groups'] = $collapsedGroups;
        $navPrefs['all_collapsed'] = $collapsed;
        
        $user->updateNavigationPreferences($navPrefs);

        return response()->json([
            'success' => true,
            'all_collapsed' => $collapsed,
            'navigation' => $navPrefs
        ]);
    }
}