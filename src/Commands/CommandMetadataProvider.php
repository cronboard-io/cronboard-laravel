<?php

namespace Cronboard\Commands;

interface CommandMetadataProvider
{
    public function getCommandName(): string;
    public function getCommandDescription(): string;
}
