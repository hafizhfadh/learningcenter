<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InstitutionScopeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Apply institution scope for school_admin and school_teacher roles
        if ($user->hasAnyRole(['school_admin', 'school_teacher'])) {
            $this->applyInstitutionScope($user);
        }

        return $next($request);
    }

    /**
     * Apply institution-level data isolation
     */
    private function applyInstitutionScope($user): void
    {
        // Set the current institution context
        if ($user->institution_id) {
            app()->instance('current_institution_id', $user->institution_id);
            
            // Store in session for persistence
            session(['current_institution_id' => $user->institution_id]);
        }
    }
}