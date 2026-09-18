<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps<{
    form: any;
    canUpdatePrice: boolean;
    categories: string[];
}>();
</script>

<template>
    <div class="space-y-4">
        <div>
            <InputLabel for="code" value="Code article" />
            <TextInput id="code" v-model="form.code" type="text" class="mt-1 block w-full" required />
            <p class="mt-1 text-xs text-gray-500">Identifiant unique interne. Champ code-barres prévu pour un futur scan.</p>
            <InputError class="mt-2" :message="form.errors.code" />
        </div>

        <div>
            <InputLabel for="barcode" value="Code-barres / QR (optionnel)" />
            <TextInput id="barcode" v-model="form.barcode" type="text" class="mt-1 block w-full" />
            <InputError class="mt-2" :message="form.errors.barcode" />
        </div>

        <div>
            <InputLabel for="name" value="Nom" />
            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="category" value="Catégorie" />
            <TextInput id="category" v-model="form.category" type="text" class="mt-1 block w-full" list="category-options" required />
            <datalist id="category-options">
                <option v-for="category in categories" :key="category" :value="category" />
            </datalist>
            <InputError class="mt-2" :message="form.errors.category" />
        </div>

        <div>
            <InputLabel for="description" value="Description" />
            <textarea
                id="description"
                v-model="form.description"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold"
                rows="3"
            />
            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div>
            <InputLabel for="sale_price" value="Prix de vente" />
            <TextInput
                id="sale_price"
                v-model="form.sale_price"
                type="number"
                min="0"
                step="0.01"
                class="mt-1 block w-full"
                required
                :disabled="!canUpdatePrice"
            />
            <p v-if="!canUpdatePrice" class="mt-1 text-xs text-gray-500">
                Vous n’êtes pas autorisé à modifier le prix.
            </p>
            <InputError class="mt-2" :message="form.errors.sale_price" />
        </div>
    </div>
</template>
