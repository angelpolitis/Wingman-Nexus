<?php
    /**
     * Project Name:    Wingman Nexus - Lazy Route Rules Fixture
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Import the following classes to the current scope.
    use Wingman\Nexus\Rules\Route;

    return [
        Route::from("lazy.api.ping", "/api/ping", "App\\Controllers\\LazyController::ping")
    ];
?>