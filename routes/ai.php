<?php

use App\Mcp\Servers\KanbrioServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', KanbrioServer::class)->middleware(['auth:sanctum', 'throttle:mcp']);
