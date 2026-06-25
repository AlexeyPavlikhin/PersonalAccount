export default {
    data() {
        return {
            current_route_name: "reports",
            available_reports: [],
            selected_report_code: "",
            selected_report: null,
            columns: [],
            rows: [],
            column_filters: {},
            sort_field: "",
            sort_direction: "DESC",
            page: 1,
            page_size: 50,
            page_size_options: [25, 50, 100],
            pagination: null,
            is_loading_reports: false,
            is_loading_data: false,
            error_message: ""
        };
    },
    mounted() {
        this.$root.check_for_permition_route(this.current_route_name);
        const navigationMenuRef = typeof this.$root.getNavigationMenuRef === "function"
            ? this.$root.getNavigationMenuRef()
            : this.$root.$refs.ref_NavigationMenu;
        if (navigationMenuRef && typeof navigationMenuRef.setActivMenuItem === "function") {
            navigationMenuRef.setActivMenuItem(this.current_route_name);
        }
        this.loadAvailableReports();
    },
    methods: {
        setSpinnerVisible(isVisible) {
            const spinner = document.getElementById("id_spinner_panel");
            if (!spinner) {
                return;
            }
            spinner.style.display = isVisible ? "block" : "none";
        },

        showError(in_message) {
            this.error_message = in_message || "";
            if (this.$root && this.$root.$refs && this.$root.$refs.ref_FormModalMessage && in_message) {
                this.$root.$refs.ref_FormModalMessage.init(this, String(in_message));
                const modal = document.getElementById("id_FormModalMessage");
                if (modal) {
                    modal.style.display = "block";
                }
            }
        },

        resetTableState() {
            this.columns = [];
            this.rows = [];
            this.column_filters = {};
            this.sort_field = "";
            this.sort_direction = "DESC";
            this.page = 1;
            this.pagination = null;
        },

        async loadAvailableReports() {
            this.is_loading_reports = true;
            this.error_message = "";
            try {
                const response = await axios.get("./queries/get_available_reports.php");
                const data = response.data || {};
                if (Number(data.ok) !== 1) {
                    this.available_reports = [];
                    this.showError(data.error || "Не удалось загрузить список отчётов");
                    return;
                }

                this.available_reports = Array.isArray(data.reports) ? data.reports : [];
                if (this.available_reports.length > 0) {
                    const first_report = this.available_reports[0];
                    this.selected_report_code = first_report.report_code || "";
                    await this.loadReportData();
                }
            } catch (error) {
                this.available_reports = [];
                this.showError("Ошибка при загрузке списка отчётов");
                console.error(error);
            } finally {
                this.is_loading_reports = false;
            }
        },

        async onReportChange() {
            this.resetTableState();
            await this.loadReportData();
        },

        async loadReportData() {
            if (!this.selected_report_code) {
                this.selected_report = null;
                this.resetTableState();
                return;
            }

            this.is_loading_data = true;
            this.setSpinnerVisible(true);
            this.error_message = "";

            try {
                const response = await axios.get("./queries/get_report_data.php", {
                    params: {
                        report_code: this.selected_report_code,
                        sort_field: this.sort_field,
                        sort_direction: this.sort_direction,
                        filters: JSON.stringify(this.column_filters || {}),
                        page: this.page,
                        page_size: this.page_size
                    }
                });
                const data = response.data || {};
                if (Number(data.ok) !== 1) {
                    this.rows = [];
                    this.pagination = null;
                    this.showError(data.error || "Не удалось загрузить данные отчёта");
                    return;
                }

                this.selected_report = data.report || null;
                this.columns = Array.isArray(data.columns) ? data.columns : [];
                this.rows = Array.isArray(data.rows) ? data.rows : [];
                if (data.sort && data.sort.field) {
                    this.sort_field = String(data.sort.field);
                    this.sort_direction = data.sort.direction === "ASC" ? "ASC" : "DESC";
                } else {
                    this.sort_field = "";
                    this.sort_direction = "DESC";
                }
                this.pagination = data.pagination || null;
                if (this.pagination && Number(this.pagination.page) > 0) {
                    this.page = Number(this.pagination.page);
                }
                if (this.pagination && Number(this.pagination.page_size) > 0) {
                    this.page_size = Number(this.pagination.page_size);
                }

                // Инициализируем фильтры для новых колонок, сохраняя уже введённые значения
                const next_filters = {};
                for (const column_item of this.columns) {
                    const field_name = column_item.field || "";
                    if (!field_name) {
                        continue;
                    }
                    next_filters[field_name] = this.column_filters[field_name] || "";
                }
                this.column_filters = next_filters;
            } catch (error) {
                this.rows = [];
                this.pagination = null;
                this.showError("Ошибка при загрузке данных отчёта");
                console.error(error);
            } finally {
                this.is_loading_data = false;
                this.setSpinnerVisible(false);
            }
        },

        onColumnSort(in_column) {
            if (!in_column || !in_column.sortable) {
                return;
            }
            const field_name = in_column.field || "";
            if (!field_name) {
                return;
            }

            if (this.sort_field === field_name) {
                this.sort_direction = this.sort_direction === "ASC" ? "DESC" : "ASC";
            } else {
                this.sort_field = field_name;
                this.sort_direction = "ASC";
            }
            this.page = 1;
            this.loadReportData();
        },

        getSortIndicator(in_column) {
            if (!in_column || !in_column.sortable) {
                return "";
            }
            const field_name = in_column.field || "";
            if (this.sort_field !== field_name) {
                return "↕";
            }
            return this.sort_direction === "ASC" ? "↑" : "↓";
        },

        onFilterApply() {
            this.page = 1;
            this.loadReportData();
        },

        onFilterClear() {
            const cleared = {};
            for (const column_item of this.columns) {
                const field_name = column_item.field || "";
                if (field_name) {
                    cleared[field_name] = "";
                }
            }
            this.column_filters = cleared;
            this.page = 1;
            this.loadReportData();
        },

        onPageChange(in_page) {
            const next_page = Number(in_page);
            if (!Number.isFinite(next_page) || next_page < 1) {
                return;
            }
            if (this.pagination && next_page > Number(this.pagination.total_pages)) {
                return;
            }
            this.page = next_page;
            this.loadReportData();
        },

        onPageSizeChange() {
            this.page = 1;
            this.loadReportData();
        },

        formatPaginationTotal(in_total) {
            const total = Number(in_total);
            if (!Number.isFinite(total) || total < 0) {
                return "0";
            }
            return total.toLocaleString("ru-RU");
        },

        getPaginationSummaryText() {
            if (!this.pagination || Number(this.pagination.total_rows) <= 0) {
                return "";
            }
            const row_from = Number(this.pagination.row_from) || 0;
            const row_to = Number(this.pagination.row_to) || 0;
            const total_rows = Number(this.pagination.total_rows) || 0;
            return "Показано " + row_from + "–" + row_to + " из " + this.formatPaginationTotal(total_rows);
        },

        // Окно номеров страниц с «…» на краях
        getVisiblePageNumbers() {
            if (!this.pagination) {
                return [];
            }
            const total_pages = Number(this.pagination.total_pages) || 0;
            const current_page = Number(this.pagination.page) || 1;
            if (total_pages <= 1) {
                return total_pages === 1 ? [1] : [];
            }

            const window_size = 2;
            const pages = [];
            const addPage = (page_num) => {
                if (page_num >= 1 && page_num <= total_pages && !pages.includes(page_num)) {
                    pages.push(page_num);
                }
            };

            addPage(1);
            for (let page_num = current_page - window_size; page_num <= current_page + window_size; page_num++) {
                addPage(page_num);
            }
            addPage(total_pages);

            pages.sort((a, b) => a - b);

            const result = [];
            for (let index = 0; index < pages.length; index++) {
                const page_num = pages[index];
                if (index > 0 && page_num - pages[index - 1] > 1) {
                    result.push("ellipsis");
                }
                result.push(page_num);
            }
            return result;
        },

        isPaginationVisible() {
            return !!(this.pagination && Number(this.pagination.total_rows) > 0);
        },

        formatCellValue(in_row, in_column) {
            if (!in_row || !in_column) {
                return "";
            }
            const field_name = in_column.field || "";
            if (!field_name || in_row[field_name] === undefined || in_row[field_name] === null) {
                return "";
            }

            const raw_value = in_row[field_name];
            if (in_column.type === "datetime" && raw_value) {
                const normalized = String(raw_value).replace(" ", "T");
                const date_obj = new Date(normalized);
                if (!Number.isNaN(date_obj.getTime())) {
                    return date_obj.toLocaleString("ru-RU");
                }
            }
            return String(raw_value);
        },

        // Тип гиперссылки для колонки: phone | email | telegram
        getColumnLinkType(in_column) {
            if (!in_column) {
                return "";
            }
            if (in_column.link_type) {
                return String(in_column.link_type);
            }
            const field_name = String(in_column.field || "");
            if (field_name === "phone") {
                return "phone";
            }
            if (field_name === "user_email" || field_name === "email") {
                return "email";
            }
            if (field_name === "telegram_nick" || field_name === "telegram") {
                return "telegram";
            }
            return "";
        },

        // Только цифры номера телефона (в БД хранятся цифры, но на всякий случай очищаем)
        normalizePhoneDigits(in_phone) {
            return String(in_phone || "").replace(/\D+/g, "");
        },

        // Формат +7 (000) 000-00-00 — как в pg_sales.js
        formate_phone(in_phone) {
            let phone_digits = this.normalizePhoneDigits(in_phone);
            if (phone_digits === "") {
                return "";
            }

            let ret = "-" + phone_digits.slice(-2);
            phone_digits = phone_digits.slice(0, -2);

            ret = "-" + phone_digits.slice(-2) + ret;
            phone_digits = phone_digits.slice(0, -2);

            ret = ") " + phone_digits.slice(-3) + ret;
            phone_digits = phone_digits.slice(0, -3);

            ret = " (" + phone_digits.slice(-3) + ret;
            phone_digits = phone_digits.slice(0, -3);

            return "+" + phone_digits + ret;
        },

        createTGLink(in_link) {
            return "https://t.me/" + String(in_link || "").replaceAll("@", "");
        },

        createMTLink(in_link) {
            return "mailto:" + String(in_link || "");
        },

        createTelLink(in_link) {
            return "tel:+" + this.normalizePhoneDigits(in_link);
        },

        getCellDisplayText(in_row, in_column) {
            const link_type = this.getColumnLinkType(in_column);
            const raw_text = this.formatCellValue(in_row, in_column);
            if (link_type === "phone" && raw_text !== "") {
                return this.formate_phone(raw_text);
            }
            return raw_text;
        },

        getCellLinkHref(in_row, in_column) {
            const link_type = this.getColumnLinkType(in_column);
            const raw_text = this.formatCellValue(in_row, in_column);
            if (raw_text === "") {
                return "";
            }
            if (link_type === "phone") {
                return this.createTelLink(raw_text);
            }
            if (link_type === "email") {
                return this.createMTLink(raw_text);
            }
            if (link_type === "telegram") {
                return this.createTGLink(raw_text);
            }
            return "";
        },

        isCellLink(in_row, in_column) {
            return this.getCellLinkHref(in_row, in_column) !== "";
        },

        // Ширина колонки в процентах от ширины экрана (п. 3.9 требований)
        getColumnWidthStyle(in_column) {
            const width_percent = parseFloat(in_column && in_column.width_percent);
            if (!Number.isFinite(width_percent) || width_percent <= 0) {
                return {};
            }
            const width_value = width_percent + "vw";
            return {
                width: width_value,
                minWidth: width_value,
                maxWidth: width_value,
                boxSizing: "border-box"
            };
        }
    },
    template: `
    <div class="msll_body reports_body">
        <div class="reports_content_card">
            <div class="reports_header">
                <h2 class="reports_title">Отчетность</h2>
                <div class="reports_selector_row">
                    <label class="reports_selector_label" for="reports_selector">Отчёт:</label>
                    <select
                        id="reports_selector"
                        class="msll_filter reports_selector"
                        v-model="selected_report_code"
                        @change="onReportChange()"
                        :disabled="is_loading_reports || available_reports.length === 0"
                    >
                        <option v-if="available_reports.length === 0" value="">Нет доступных отчётов</option>
                        <option
                            v-for="report_item in available_reports"
                            :key="report_item.report_code"
                            :value="report_item.report_code"
                        >
                            {{ report_item.report_name }}
                        </option>
                    </select>
                </div>
            </div>

            <p class="reports_selected_name" v-if="selected_report && selected_report.report_name">
                {{ selected_report.report_name }}
            </p>
            <p class="reports_description" v-if="selected_report && selected_report.report_description">
                {{ selected_report.report_description }}
            </p>

            <div class="reports_table_wrap" v-if="selected_report_code">
                <table class="msll_table reports_table">
                    <colgroup>
                        <col
                            v-for="column_item in columns"
                            :key="'col-' + column_item.field"
                            :style="getColumnWidthStyle(column_item)"
                        >
                    </colgroup>
                    <thead>
                        <tr>
                            <th
                                v-for="column_item in columns"
                                :key="'head-' + column_item.field"
                                :class="{ 'reports_th_sortable': column_item.sortable }"
                                :style="getColumnWidthStyle(column_item)"
                                @click="onColumnSort(column_item)"
                            >
                                <span class="reports_th_label">{{ column_item.label }}</span>
                                <span class="reports_th_sort" v-if="column_item.sortable">{{ getSortIndicator(column_item) }}</span>
                            </th>
                        </tr>
                        <tr class="reports_filter_row">
                            <th
                                v-for="column_item in columns"
                                :key="'filter-' + column_item.field"
                                :style="getColumnWidthStyle(column_item)"
                            >
                                <input
                                    v-if="column_item.filterable"
                                    class="msll_filter reports_filter_input"
                                    type="text"
                                    v-model="column_filters[column_item.field]"
                                    :placeholder="'Фильтр: ' + column_item.label"
                                    @keyup.enter="onFilterApply()"
                                >
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row_item, row_index) in rows" :key="'row-' + row_index">
                            <td
                                v-for="column_item in columns"
                                :key="'cell-' + row_index + '-' + column_item.field"
                                :style="getColumnWidthStyle(column_item)"
                            >
                                <a
                                    v-if="isCellLink(row_item, column_item)"
                                    :href="getCellLinkHref(row_item, column_item)"
                                    target="_blank"
                                >{{ getCellDisplayText(row_item, column_item) }}</a>
                                <template v-else>{{ formatCellValue(row_item, column_item) }}</template>
                            </td>
                        </tr>
                        <tr v-if="!is_loading_data && rows.length === 0">
                            <td :colspan="columns.length || 1" class="reports_empty_cell">
                                По текущим условиям данных не найдено.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="reports_pagination" v-if="isPaginationVisible()">
                <div class="reports_pagination_info">
                    <span class="reports_pagination_summary">{{ getPaginationSummaryText() }}</span>
                    <label class="reports_pagination_size_label">
                        Строк на странице:
                        <select
                            class="msll_filter reports_pagination_page_size"
                            v-model.number="page_size"
                            @change="onPageSizeChange()"
                            :disabled="is_loading_data"
                        >
                            <option
                                v-for="size_option in page_size_options"
                                :key="'page-size-' + size_option"
                                :value="size_option"
                            >
                                {{ size_option }}
                            </option>
                        </select>
                    </label>
                </div>
                <div class="reports_pagination_controls">
                    <input
                        class="msll_middle_button reports_pagination_nav"
                        type="button"
                        value="← Назад"
                        @click="onPageChange(page - 1)"
                        :disabled="is_loading_data || page <= 1"
                    >
                    <div class="reports_pagination_pages">
                        <template v-for="(page_item, page_index) in getVisiblePageNumbers()" :key="'page-item-' + page_index">
                            <span v-if="page_item === 'ellipsis'" class="reports_pagination_ellipsis">…</span>
                            <button
                                v-else
                                type="button"
                                class="reports_pagination_page"
                                :class="{ 'reports_pagination_page_active': page_item === page }"
                                @click="onPageChange(page_item)"
                                :disabled="is_loading_data || page_item === page"
                            >
                                {{ page_item }}
                            </button>
                        </template>
                    </div>
                    <input
                        class="msll_middle_button reports_pagination_nav"
                        type="button"
                        value="Вперёд →"
                        @click="onPageChange(page + 1)"
                        :disabled="is_loading_data || !pagination || page >= pagination.total_pages"
                    >
                </div>
            </div>

            <div class="reports_actions" v-if="selected_report_code && columns.length > 0">
                <input class="msll_middle_button" type="button" value="Применить фильтры" @click="onFilterApply()" :disabled="is_loading_data">
                <input class="msll_middle_button" type="button" value="Сбросить фильтры" @click="onFilterClear()" :disabled="is_loading_data">
            </div>

            <div class="reports_hint" v-if="!is_loading_reports && available_reports.length === 0">
                У вас нет доступа ни к одному отчёту. Обратитесь к администратору для назначения полномочий.
            </div>
        </div>
    </div>
    `
};
