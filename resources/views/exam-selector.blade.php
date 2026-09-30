<div x-data="examSelector()" class="space-y-6">
    {{-- SEARCH BAR --}}
    <div class="cn-embedded-search">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="cn-embedded-search__icon" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input
            type="text"
            x-model="search"
            @input="filterExams()"
            placeholder="Buscar exámenes por nombre..."
            class="cn-embedded-search__input"
        >
    </div>

    {{-- LABORATORIO BLOCK (x-show reacciona al cambio de data.type sin depender solo del re-render del View) --}}
    @if ($laboratoryCategories->count() > 0)
        <div
            class="cn-exam-type-panel cn-exam-type-panel--lab"
            x-show="currentOrderType() === 'laboratorio'"
            x-cloak
        >
            <div class="cn-exam-type-panel__header">
                <h3 class="cn-exam-type-panel__title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                    </svg>
                    Laboratorio
                    <span class="text-sm font-normal opacity-80" x-text="`(${getVisibleLabExamsCount()} exámenes)`"></span>
                </h3>
            </div>

            <div class="cn-exam-type-panel__body space-y-3">
                @foreach ($laboratoryCategories as $category)
                    <div
                        class="category-item"
                        x-data="{ expanded: false }"
                        x-show="isCategoryVisible('lab', {{ $category->id }})"
                        x-transition
                    >
                        <button
                            type="button"
                            @click="expanded = !expanded"
                            class="w-full flex items-center justify-between py-2 px-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition text-left"
                        >
                            <div class="flex items-center gap-3 flex-1">
                                <svg x-show="!expanded" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 dark:text-blue-400 shrink-0" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                                <svg x-show="expanded" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 dark:text-blue-400 shrink-0" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                                <span class="font-semibold cn-embedded-text">{{ $category->name }}</span>
                                <span class="text-xs bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-200 px-2 py-1 rounded-full ml-auto" x-text="`${getVisibleExamsInCategory('lab', {{ $category->id }}).length}`"></span>
                            </div>
                        </button>

                        <div x-show="expanded" x-transition class="ml-8 space-y-2 mt-2">
                            @foreach ($category->exams as $exam)
                                <label
                                    class="exam-checkbox flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/30 transition"
                                    data-exam-id="{{ $exam->id }}"
                                    data-exam-name="{{ strtolower($exam->name) }}"
                                    x-show="isExamVisible('lab', {{ $category->id }}, '{{ strtolower($exam->name) }}')"
                                    x-transition
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $exam->id }}"
                                        :checked="isExamChecked({{ $exam->id }})"
                                        @change="toggleExam({{ $exam->id }}, false)"
                                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-600 dark:border-gray-500 cursor-pointer"
                                    >
                                    <div class="flex-1">
                                        <span class="cn-embedded-text font-medium">{{ $exam->name }}</span>
                                        <span class="text-xs cn-embedded-muted ml-2">BOB {{ number_format($exam->price, 2) }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- IMAGEN BLOCK --}}
    @if ($imagingCategories->count() > 0)
        <div
            class="cn-exam-type-panel cn-exam-type-panel--img"
            x-show="currentOrderType() === 'imagen'"
            x-cloak
        >
            <div class="cn-exam-type-panel__header">
                <h3 class="cn-exam-type-panel__title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                    </svg>
                    Imagen
                    <span class="text-sm font-normal opacity-80" x-text="`(${getVisibleImgExamsCount()} exámenes)`"></span>
                </h3>
            </div>

            <div class="cn-exam-type-panel__body space-y-3">
                @foreach ($imagingCategories as $category)
                    <div
                        class="category-item"
                        x-data="{ expanded: false }"
                        x-show="isCategoryVisible('img', {{ $category->id }})"
                        x-transition
                    >
                        <button
                            type="button"
                            @click="expanded = !expanded"
                            class="w-full flex items-center justify-between py-2 px-3 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition text-left"
                        >
                            <div class="flex items-center gap-3 flex-1">
                                <svg x-show="!expanded" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-green-500 dark:text-green-400 shrink-0" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                                <svg x-show="expanded" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-green-500 dark:text-green-400 shrink-0" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                                <span class="font-semibold cn-embedded-text">{{ $category->name }}</span>
                                <span class="text-xs bg-green-100 dark:bg-green-900/60 text-green-700 dark:text-green-200 px-2 py-1 rounded-full ml-auto" x-text="`${getVisibleExamsInCategory('img', {{ $category->id }}).length}`"></span>
                            </div>
                        </button>

                        <div x-show="expanded" x-transition class="ml-8 space-y-2 mt-2">
                            @foreach ($category->exams as $exam)
                                <label
                                    class="exam-checkbox flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-green-50 dark:hover:bg-green-900/30 transition"
                                    data-exam-id="{{ $exam->id }}"
                                    data-exam-name="{{ strtolower($exam->name) }}"
                                    x-show="isExamVisible('img', {{ $category->id }}, '{{ strtolower($exam->name) }}')"
                                    x-transition
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $exam->id }}"
                                        :checked="isExamChecked({{ $exam->id }})"
                                        @change="toggleExam({{ $exam->id }}, true)"
                                        class="w-4 h-4 text-green-600 rounded focus:ring-2 focus:ring-green-500 dark:bg-gray-600 dark:border-gray-500 cursor-pointer"
                                    >
                                    <div class="flex-1">
                                        <span class="cn-embedded-text font-medium">{{ $exam->name }}</span>
                                        <span class="text-xs cn-embedded-muted ml-2">BOB {{ number_format($exam->price, 2) }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Requisitos previos --}}
    <div
        class="cn-embedded-panel"
        x-show="selectedExamsDetails().length > 0"
        x-cloak
    >
        <h4 class="cn-embedded-panel__title">Requisitos previos de los exámenes seleccionados</h4>
        <div>
            <template x-for="exam in selectedExamsDetails()" :key="exam.id">
                <div class="cn-embedded-card">
                    <p class="cn-embedded-card__title" x-text="exam.name"></p>
                    <template x-if="examRequirementsList(exam).length === 0">
                        <p class="cn-embedded-card__empty">Sin requisitos previos.</p>
                    </template>
                    <ul class="cn-embedded-card__list" x-show="examRequirementsList(exam).length > 0">
                        <template x-for="req in examRequirementsList(exam)" :key="req.id">
                            <li x-text="req.description"></li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
    </div>

    {{-- EMPTY STATE --}}
    <div
        x-show="getVisibleLabExamsCount() === 0 && getVisibleImgExamsCount() === 0 && search.length > 0"
        x-transition
        class="text-center py-8 cn-embedded-muted"
    >
        <div class="flex justify-center mb-2 opacity-40">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </div>
        <p class="text-lg font-medium cn-embedded-text">No se encontraron exámenes</p>
        <p class="text-sm">Intenta con otro término de búsqueda</p>
    </div>
</div>

<script>
function examSelector() {
    const laboratoryCategories = @json($laboratoryCategories);
    const imagingCategories = @json($imagingCategories);

    const labExamIds = new Set(
        laboratoryCategories.flatMap(c => (c.exams ?? []).map(e => Number(e.id)))
    );
    const imagingExamIds = new Set(
        imagingCategories.flatMap(c => (c.exams ?? []).map(e => Number(e.id)))
    );

    return {
        search: '',
        selectedExams: [],
        _lastOrderType: null,

        init() {
            this.$nextTick(() => {
                this.syncFromWire();
                this._lastOrderType = this.currentOrderType();
            });

            this.$wire.$watch('data.type', (type) => {
                const normalized = type === 'imagen' ? 'imagen' : 'laboratorio';
                if (this._lastOrderType !== null && normalized !== this._lastOrderType) {
                    this.clearExamsOnTypeChange();
                }
                this._lastOrderType = normalized;
            });
        },

        currentOrderType() {
            return this.$wire.get('data.type') ?? 'laboratorio';
        },

        allowedExamIdsForCurrentType() {
            return this.currentOrderType() === 'imagen' ? imagingExamIds : labExamIds;
        },

        syncFromWire() {
            const current = this.$wire.get('data.exams');
            const allowed = this.allowedExamIdsForCurrentType();
            this.selectedExams = Array.isArray(current)
                ? current.map(Number).filter(id => allowed.has(id))
                : [];
            if (this.selectedExams.length !== (Array.isArray(current) ? current.length : 0)) {
                this.$wire.set('data.exams', this.selectedExams);
            }
            this.syncEquipmentFromSelection();
        },

        clearExamsOnTypeChange() {
            this.selectedExams = [];
            this.$wire.set('data.exams', []);
            this.$wire.set('data.equipment_id', null);
        },

        isExamChecked(examId) {
            return this.selectedExams.includes(Number(examId));
        },

        findExamById(examId) {
            const id = Number(examId);
            const all = [
                ...laboratoryCategories.flatMap(c => c.exams ?? []),
                ...imagingCategories.flatMap(c => c.exams ?? []),
            ];

            return all.find(e => Number(e.id) === id) ?? null;
        },

        selectedExamsDetails() {
            const allowed = this.allowedExamIdsForCurrentType();

            return this.selectedExams
                .filter(id => allowed.has(Number(id)))
                .map(id => this.findExamById(id))
                .filter(exam => exam !== null);
        },

        examRequirementsList(exam) {
            if (! exam || ! exam.requirements) {
                return [];
            }

            return Array.isArray(exam.requirements) ? exam.requirements : [];
        },

        syncEquipmentFromSelection() {
            const orderType = this.$wire.get('data.type');
            if (orderType !== 'imagen') {
                return;
            }
            const imagingIds = new Set(imagingCategories.flatMap(c => c.exams.map(e => Number(e.id))));
            const selectedImaging = this.selectedExams.filter(eid => imagingIds.has(Number(eid)));
            if (selectedImaging.length === 1) {
                const exam = imagingCategories.flatMap(c => c.exams).find(e => Number(e.id) === Number(selectedImaging[0]));
                if (exam && exam.imaging_equipment_id) {
                    this.$wire.set('data.equipment_id', exam.imaging_equipment_id);
                    return;
                }
            }
            this.$wire.set('data.equipment_id', null);
        },

        toggleExam(examId, isImagingSection) {
            const id = Number(examId);
            const allowed = this.allowedExamIdsForCurrentType();
            if (! allowed.has(id)) {
                return;
            }

            this.selectedExams = this.selectedExams.filter(eid => allowed.has(Number(eid)));

            const idx = this.selectedExams.indexOf(id);
            if (idx === -1) {
                if (isImagingSection) {
                    this.selectedExams = this.selectedExams.filter(eid => ! imagingExamIds.has(Number(eid)));
                }
                this.selectedExams.push(id);
            } else {
                this.selectedExams.splice(idx, 1);
            }
            this.$wire.set('data.exams', this.selectedExams);
            this.syncEquipmentFromSelection();
        },

        filterExams() {
            // Alpine reacciona automáticamente a los cambios en visibilidad
        },

        isCategoryVisible(type, categoryId) {
            const categories = type === 'lab' ? laboratoryCategories : imagingCategories;
            const category = categories.find(c => c.id === categoryId);

            if (!category) return false;
            if (!this.search) return true;

            return category.exams.some(exam =>
                exam.name.toLowerCase().includes(this.search.toLowerCase())
            );
        },

        isExamVisible(type, categoryId, examName) {
            if (!this.search) return true;

            return examName.toLowerCase().includes(this.search.toLowerCase());
        },

        getVisibleExamsInCategory(type, categoryId) {
            const categories = type === 'lab' ? laboratoryCategories : imagingCategories;
            const category = categories.find(c => c.id === categoryId);

            if (!category) return [];

            if (!this.search) return category.exams;

            return category.exams.filter(exam =>
                exam.name.toLowerCase().includes(this.search.toLowerCase())
            );
        },

        getVisibleLabExamsCount() {
            if (!this.search) {
                return laboratoryCategories.reduce((sum, cat) => sum + cat.exams.length, 0);
            }

            return laboratoryCategories.reduce((sum, cat) => {
                const visible = cat.exams.filter(exam =>
                    exam.name.toLowerCase().includes(this.search.toLowerCase())
                );
                return sum + visible.length;
            }, 0);
        },

        getVisibleImgExamsCount() {
            if (!this.search) {
                return imagingCategories.reduce((sum, cat) => sum + cat.exams.length, 0);
            }

            return imagingCategories.reduce((sum, cat) => {
                const visible = cat.exams.filter(exam =>
                    exam.name.toLowerCase().includes(this.search.toLowerCase())
                );
                return sum + visible.length;
            }, 0);
        }
    };
}
</script>
