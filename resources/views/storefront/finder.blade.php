@extends('layouts.storefront')

@section('title', 'Equipment Finder — Farmtech')
@section('meta_description', 'Answer two questions and we\'ll point you at the right Farmtech equipment category.')

@section('content')
    <div x-data="{
            step: 1,
            industries: @js($industries),
            iconSvgs: @js($iconSvgs),
            industry: null,
            goal: null,
            get selectedIndustry() { return this.industries.find(i => i.value === this.industry) ?? null; },
            pickIndustry(value) { this.industry = value; this.goal = null; this.step = 2; },
            pickGoal(goal) { this.goal = goal; this.step = 3; },
            reset() { this.step = 1; this.industry = null; this.goal = null; },
         }"
         class="max-w-4xl mx-auto px-4 py-12">

        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold text-center">Equipment Finder</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mt-1 text-center">What are you trying to achieve?</h1>
        <p class="text-ink-secondary mt-2 text-center max-w-xl mx-auto">Two quick questions, then straight to the real equipment that fits.</p>

        {{-- Step indicator --}}
        <div class="flex items-center justify-center gap-2 mt-8 mb-10">
            <template x-for="n in 3" :key="n">
                <div class="flex items-center gap-2">
                    <span :class="step >= n ? 'bg-mint text-white' : 'bg-canvas text-ink-muted border border-border'"
                          class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold transition" x-text="n"></span>
                    <div x-show="n < 3" class="w-10 h-px" :class="step > n ? 'bg-mint' : 'bg-border'"></div>
                </div>
            </template>
        </div>

        {{-- Step 1: Industry --}}
        <div x-show="step === 1" x-transition>
            <h2 class="font-display font-semibold text-lg text-charcoal mb-4 text-center">1. Which industry are you working in?</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <template x-for="ind in industries" :key="ind.value">
                    <button type="button" @click="pickIndustry(ind.value)" class="card p-6 text-left hover:border-mint/40">
                        <span class="font-display font-semibold text-charcoal" x-text="ind.label"></span>
                        <span class="block text-sm text-ink-secondary mt-1" x-text="ind.goals.map(g => g.label).join(', ')"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Step 2: Goal --}}
        <div x-show="step === 2" x-transition x-cloak>
            <button type="button" @click="step = 1" class="text-sm font-semibold text-ink-secondary hover:text-charcoal transition mb-4 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </button>
            <h2 class="font-display font-semibold text-lg text-charcoal mb-4 text-center">2. What are you trying to do?</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <template x-if="selectedIndustry">
                    <template x-for="g in selectedIndustry.goals" :key="g.label">
                        <button type="button" @click="pickGoal(g)" class="card p-5 text-left hover:border-mint/40 flex items-start gap-3">
                            <span class="w-9 h-9 rounded-lg bg-mint/10 text-mint-dark flex items-center justify-center flex-shrink-0" x-html="iconSvgs[g.icon]"></span>
                            <span>
                                <span class="block font-semibold text-charcoal text-sm" x-text="g.label"></span>
                                <span class="block text-xs text-ink-secondary mt-0.5" x-text="g.description"></span>
                            </span>
                        </button>
                    </template>
                </template>
            </div>
        </div>

        {{-- Step 3: Result --}}
        <div x-show="step === 3" x-transition x-cloak>
            <template x-if="goal">
                <div class="card p-8 text-center max-w-lg mx-auto">
                    <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold">Recommended category</p>
                    <p class="font-display font-bold text-xl text-charcoal mt-1" x-text="goal.label"></p>
                    <p class="text-sm text-ink-secondary mt-2" x-text="goal.description"></p>
                    <a :href="goal.url" class="mt-6 inline-flex items-center justify-center gap-2 bg-mint hover:bg-mint-dark text-white font-semibold px-8 py-3 rounded-full transition">
                        See equipment
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                    <button type="button" @click="reset()" class="block mx-auto mt-4 text-sm font-semibold text-ink-secondary hover:text-charcoal transition">Start over</button>
                </div>
            </template>
        </div>
    </div>
@endsection
