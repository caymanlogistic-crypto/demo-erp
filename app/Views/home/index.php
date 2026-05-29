<?php
/**
 * Страница «Общая информация» — демонстрационный режим.
 *
 * Переменные:
 *   $title      — string
 *   $pageTitle  — string
 *   $pageModule — string ('home')
 */
?>
<div class="home-overview">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <div class="page-head">
        <div class="page-head-left">
            <div class="page-title">Общая информация</div>
            <div class="page-summary"><span>Демонстрационный режим системы учёта вывоза отходов I–II классов опасности</span></div>
        </div>
    </div>

    <!-- ============================================================
         INTRO CARD
         ============================================================ -->
    <section class="home-hero-card">
        <div class="home-card-head">
            <span class="home-card-icon home-card-icon--info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
            </span>
            <div class="home-card-head-title">О демонстрационном режиме</div>
        </div>
        <div class="home-card-text">
            <p>Демонстрационная версия предназначена для ознакомления с возможностями системы учёта и контроля вывоза отходов I–II классов опасности.</p>
            <p>В этом режиме предоставляется частичный доступ к функционалу. Данные носят демонстрационный характер и могут отличаться от реальных.</p>
        </div>
    </section>

    <!-- ============================================================
         MODULE CARDS — 4 columns
         ============================================================ -->
    <div class="home-section-grid">

        <!-- Card 1: Доступные заявки -->
        <article class="home-module-card">
            <div class="home-module-head">
                <span class="home-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </span>
                <div class="home-module-num">1</div>
            </div>
            <div class="home-module-title">Доступные заявки</div>
            <div class="home-module-text">
                Раздел показывает доступные заявки из ФГИС. Поддерживается фильтрация по статусам и датам, а также быстрый поиск по одной или нескольким заявкам.
                В поле «Фильтр по заявкам» можно вставить номера через пробел, запятую или списком из Excel.
            </div>
        </article>

        <!-- Card 2: Планирование и вывоз -->
        <article class="home-module-card">
            <div class="home-module-head">
                <span class="home-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13" rx="2" ry="2"/>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                        <line x1="8" y1="8" x2="12" y2="8"/>
                        <line x1="8" y1="12" x2="14" y2="12"/>
                    </svg>
                </span>
                <div class="home-module-num">2</div>
            </div>
            <div class="home-module-title">Планирование и вывоз</div>
            <div class="home-module-text">
                Раздел отображает запланированные к вывозу отправки, рейсы в пути и завершённые рейсы.
                При выборе рейса раскрывается список включённых заявок и основная информация по ним.
            </div>
        </article>

        <!-- Card 3: Статистика -->
        <article class="home-module-card">
            <div class="home-module-head">
                <span class="home-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                        <line x1="2" y1="20" x2="22" y2="20"/>
                    </svg>
                </span>
                <div class="home-module-num">3</div>
            </div>
            <div class="home-module-title">Статистика</div>
            <div class="home-module-text">
                Раздел содержит оперативную статистику работы с системой ФРЕГАТ. Основной акцент сделан на количестве заявок, общем весе, динамике по периодам и сравнении показателей.
            </div>
        </article>

        <!-- Card 4: Отчетность по регионам -->
        <article class="home-module-card">
            <div class="home-module-head">
                <span class="home-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        <path d="M12 2a15.3 15.3 0 0 0-4 10 15.3 15.3 0 0 0 4 10"/>
                    </svg>
                </span>
                <div class="home-module-num">4</div>
            </div>
            <div class="home-module-title">Отчетность по регионам</div>
            <div class="home-module-text">
                Раздел предназначен для анализа выполненной работы в разрезе федеральных округов и регионов.
                Отчёты позволяют видеть распределение заявок и веса по территориям, сравнивать периоды и оценивать структуру работ.
            </div>
        </article>

    </div>

    <!-- ============================================================
         DEVELOPMENT + NOTIFICATION block
         ============================================================ -->
    <div class="home-development-grid">

        <!-- LEFT: Возможности развития системы -->
        <section class="home-dev-card">
            <div class="home-card-head">
                <span class="home-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </span>
                <div class="home-card-head-title">Возможности развития системы</div>
            </div>
            <div class="home-feature-grid">

                <div class="home-feature-item">
                    <span class="home-card-icon home-card-icon--sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    <div>
                        <div class="home-feature-title">Распределение заявок между подрядчиками</div>
                        <div class="home-feature-text">Автоматическое или ручное распределение заявок между подрядчиками с учётом объёмов, федеральных округов, регионов, загрузки и статусов.</div>
                    </div>
                </div>

                <div class="home-feature-item">
                    <span class="home-card-icon home-card-icon--sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </span>
                    <div>
                        <div class="home-feature-title">Контроль цен</div>
                        <div class="home-feature-text">Учёт индивидуально согласованных цен, сравнение предложений подрядчиков и контроль соответствия договорным условиям.</div>
                    </div>
                </div>

                <div class="home-feature-item">
                    <span class="home-card-icon home-card-icon--sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </span>
                    <div>
                        <div class="home-feature-title">Контроль выполнения плана подрядчика</div>
                        <div class="home-feature-text">Сравнение плановых и фактических данных, уведомления о просрочках, напоминания о плане и формирование контрольных отчётов.</div>
                    </div>
                </div>

                <div class="home-feature-item">
                    <span class="home-card-icon home-card-icon--sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
                        </svg>
                    </span>
                    <div>
                        <div class="home-feature-title">База «горящих» заявок</div>
                        <div class="home-feature-text">Выделение приоритетных заявок, требующих срочного вывоза, и ускоренная передача подрядчикам для включения в план.</div>
                    </div>
                </div>

            </div>
        </section>

        <!-- RIGHT: Автоматическое информирование -->
        <section class="home-notify-card">
            <div class="home-card-head">
                <span class="home-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </span>
                <div class="home-card-head-title">Автоматическое информирование</div>
            </div>
            <div class="home-card-text">
                <p>Система поддерживает автоматическую отправку уведомлений по электронной почте и через мессенджер MAX.</p>
                <p class="home-sub-head">Доступные уведомления:</p>
                <ul class="home-bullet-list">
                    <li>напоминания о смене статусов заявок и рейсов;</li>
                    <li>напоминания о сроках подачи планов;</li>
                    <li>уведомления о значимых событиях и отклонениях;</li>
                    <li>результаты проверок и согласований.</li>
                </ul>
            </div>
        </section>

    </div>

    <!-- ============================================================
         NOTE CARD
         ============================================================ -->
    <section class="home-note-card">
        <div class="home-card-head">
            <span class="home-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </span>
            <div class="home-card-head-title">Примечание</div>
        </div>
        <div class="home-card-text">
            <p>Представленные сценарии являются базовыми. Система может быть расширена дополнительными модулями и настройками в соответствии с бизнес-процессами вашей организации.</p>
        </div>
    </section>

</div>
