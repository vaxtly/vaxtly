<?php

use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return view('components.docs-page.docs-page')
            ->layout('layouts.app', ['title' => 'Vaxtly User Guide']);
    }
};
