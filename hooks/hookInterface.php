<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

interface HookInterface
{
    public function __construct(Module $module, array $data = []);
}