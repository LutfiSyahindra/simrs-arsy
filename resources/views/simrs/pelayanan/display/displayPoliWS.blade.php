<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Display Antrian Poli WS</title>

        <link rel="stylesheet" href="{{ asset("assets/vendors/core/core.css") }}">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset("assets/fonts/feather-font/css/iconfont.css") }}">
        <link rel="stylesheet" href="{{ asset("assets/vendors/flag-icon-css/css/flag-icon.min.css") }}">
        <link rel="stylesheet" href="{{ asset("assets/css/demo1/style.css") }}">
        <link rel="shortcut icon" href="{{ asset("assets/images/favicon.png") }}" />
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <style>
            :root {
                --display-bg: #eef5f4;
                --display-ink: #102a2f;
                --display-muted: #64747a;
                --display-line: #d7e5e5;
                --display-teal: #087f8c;
                --display-cyan: #0ea5a3;
                --display-amber: #f59e0b;
                --display-green: #16a34a;
                --display-blue: #2563eb;
                --display-red: #dc2626;
                --display-deep: #07414a;
                --display-white: #ffffff;
                --display-shadow: 0 16px 42px rgba(16, 42, 47, .12);
                --display-shadow-strong: 0 24px 68px rgba(7, 65, 74, .2);
            }

            * {
                box-sizing: border-box;
            }

            html,
            body {
                height: 100%;
            }

            body {
                margin: 0;
                min-height: 100vh;
                overflow: hidden;
                color: var(--display-ink);
                background:
                    linear-gradient(135deg, rgba(8, 127, 140, .13) 0, transparent 34%),
                    linear-gradient(315deg, rgba(37, 99, 235, .08) 0, transparent 32%),
                    linear-gradient(90deg, rgba(8, 127, 140, .08) 1px, transparent 1px),
                    linear-gradient(180deg, rgba(8, 127, 140, .06) 1px, transparent 1px),
                    var(--display-bg);
                background-size: 64px 64px;
                font-family: Arial, Helvetica, sans-serif;
                letter-spacing: 0;
            }

            body::before {
                content: "";
                position: fixed;
                inset: 0;
                z-index: 0;
                pointer-events: none;
                background: linear-gradient(115deg, transparent 0 42%, rgba(255, 255, 255, .38) 50%, transparent 58% 100%);
                opacity: .28;
                transform: translateX(-75%);
                animation: ambientSweep 14s linear infinite;
            }

            .display-shell {
                position: relative;
                z-index: 1;
                min-height: 100vh;
                display: grid;
                grid-template-rows: auto minmax(0, 1fr) auto;
                gap: 14px;
                padding: 18px;
            }

            .display-header {
                min-height: 86px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: 18px;
                border: 1px solid rgba(255, 255, 255, .42);
                border-radius: 8px;
                background: linear-gradient(120deg, var(--display-deep), var(--display-teal) 58%, #0f9f97);
                color: #fff;
                box-shadow: var(--display-shadow);
                padding: 14px 16px;
                position: relative;
                overflow: hidden;
            }

            .display-header::after {
                content: "";
                position: absolute;
                inset: 0;
                pointer-events: none;
                background: linear-gradient(100deg, transparent 0 38%, rgba(255, 255, 255, .18) 52%, transparent 68% 100%);
                transform: translateX(-85%);
                animation: headerSweep 9s ease-in-out infinite;
            }

            .display-header > * {
                position: relative;
                z-index: 1;
            }

            .brand-group {
                min-width: 0;
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .brand-logo {
                width: 66px;
                height: 66px;
                flex: 0 0 66px;
                border-radius: 8px;
                background: #fff;
                object-fit: contain;
                padding: 6px;
                box-shadow: 0 10px 26px rgba(3, 47, 54, .24);
            }

            .brand-title {
                min-width: 0;
            }

            .brand-title h1 {
                margin: 0;
                font-size: 30px;
                line-height: 1.08;
                font-weight: 800;
                letter-spacing: 0;
                text-transform: uppercase;
            }

            .brand-title p {
                margin: 6px 0 0;
                color: rgba(255, 255, 255, .84);
                font-size: 15px;
                font-weight: 700;
            }

            .header-tools {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .clock-box,
            .sound-state,
            .fullscreen-btn {
                height: 50px;
                border: 1px solid rgba(255, 255, 255, .24);
                border-radius: 8px;
                background: rgba(255, 255, 255, .12);
                color: #fff;
                backdrop-filter: blur(8px);
                transition: transform .18s ease, background .18s ease, border-color .18s ease;
            }

            .clock-box {
                min-width: 168px;
                display: grid;
                place-items: center;
                padding: 6px 14px;
                text-align: right;
            }

            .clock-time {
                font-size: 22px;
                line-height: 1;
                font-weight: 800;
            }

            .clock-date {
                margin-top: 4px;
                color: rgba(255, 255, 255, .78);
                font-size: 11px;
                font-weight: 700;
            }

            .sound-state {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 0 13px;
                font-weight: 800;
                white-space: nowrap;
                cursor: pointer;
            }

            .sound-state:not(.is-ready) {
                animation: standbyPulse 2.2s ease-in-out infinite;
            }

            .sound-dot {
                width: 9px;
                height: 9px;
                border-radius: 999px;
                background: var(--display-amber);
                box-shadow: 0 0 0 4px rgba(245, 158, 11, .18);
            }

            .sound-state.is-ready .sound-dot {
                background: #4ade80;
                box-shadow: 0 0 0 4px rgba(74, 222, 128, .2);
            }

            .fullscreen-btn {
                width: 50px;
                display: grid;
                place-items: center;
                border: 1px solid rgba(255, 255, 255, .24);
                cursor: pointer;
            }

            .sound-state:hover,
            .fullscreen-btn:hover {
                transform: translateY(-2px);
                background: rgba(255, 255, 255, .2);
                border-color: rgba(255, 255, 255, .42);
            }

            .fullscreen-btn:focus-visible {
                outline: 3px solid rgba(255, 255, 255, .7);
                outline-offset: 3px;
            }

            .fullscreen-btn i {
                font-size: 25px;
            }

            .display-main {
                min-height: 0;
                display: grid;
                grid-template-columns: minmax(0, 1.38fr) minmax(320px, .62fr);
                gap: 14px;
                overflow: hidden;
            }

            .call-panel,
            .side-panel {
                min-width: 0;
                min-height: 0;
                border: 1px solid var(--display-line);
                border-radius: 8px;
                background: rgba(255, 255, 255, .96);
                box-shadow: var(--display-shadow);
            }

            .call-panel {
                position: relative;
                display: grid;
                grid-template-rows: auto minmax(0, 1fr) auto;
                overflow: hidden;
                background:
                    linear-gradient(160deg, rgba(255, 255, 255, .98), rgba(236, 253, 245, .92)),
                    #fff;
                box-shadow: var(--display-shadow-strong);
                isolation: isolate;
            }

            .call-panel::before {
                content: "";
                position: absolute;
                inset: 0 0 auto;
                height: 8px;
                background: linear-gradient(90deg, var(--display-teal), var(--display-cyan), var(--display-blue));
            }

            .call-panel::after {
                content: "";
                position: absolute;
                inset: auto -18% -32% 30%;
                height: 52%;
                z-index: -1;
                background: linear-gradient(135deg, rgba(14, 165, 163, .16), rgba(37, 99, 235, .08));
                transform: rotate(-4deg);
            }

            .call-panel.is-speaking {
                border-color: rgba(37, 99, 235, .38);
            }

            .call-status {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                padding: 24px 26px 12px;
            }

            .call-status-label {
                display: inline-flex;
                align-items: center;
                gap: 9px;
                color: var(--display-teal);
                font-size: 16px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .call-status-label i {
                font-size: 24px;
            }

            .queue-count {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border: 1px solid #fde68a;
                border-radius: 8px;
                background: #fffbeb;
                color: #92400e;
                padding: 8px 12px;
                font-size: 14px;
                font-weight: 900;
                transition: transform .18s ease, background .18s ease, border-color .18s ease;
            }

            .queue-count.has-pending {
                border-color: #fbbf24;
                background: #fef3c7;
            }

            .queue-count.is-bumped,
            .stat-item.is-bumped {
                animation: bump .34s ease;
            }

            .call-content {
                display: grid;
                place-items: center;
                padding: 8px 28px 16px;
                text-align: center;
            }

            .call-stage {
                width: min(100%, 720px);
                position: relative;
                display: grid;
                justify-items: center;
                gap: 10px;
                padding: 12px 20px 4px;
            }

            .call-stage::before,
            .call-stage::after {
                content: "";
                position: absolute;
                inset: 38px 42px 34px;
                border: 1px solid rgba(8, 127, 140, .16);
                border-radius: 8px;
                opacity: .7;
                transform: scale(.94);
                pointer-events: none;
            }

            .call-stage::after {
                inset: 22px 18px 18px;
                border-color: rgba(37, 99, 235, .12);
                transform: scale(1.02);
            }

            .call-panel.is-speaking .call-stage::before {
                animation: ringPulse 1.2s ease-out infinite;
            }

            .call-panel.is-speaking .call-stage::after {
                animation: ringPulse 1.2s ease-out .18s infinite;
            }

            .call-eyebrow {
                position: relative;
                z-index: 1;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border: 1px solid rgba(8, 127, 140, .16);
                border-radius: 8px;
                background: rgba(255, 255, 255, .86);
                color: var(--display-muted);
                padding: 8px 12px;
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .call-eyebrow::before {
                content: "";
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: var(--display-cyan);
                box-shadow: 0 0 0 5px rgba(14, 165, 163, .14);
            }

            .queue-number {
                position: relative;
                z-index: 1;
                min-height: 156px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--display-teal);
                font-size: 132px;
                line-height: .92;
                font-weight: 900;
                text-shadow: 0 8px 28px rgba(8, 127, 140, .18);
                transition: transform .22s ease, color .22s ease;
            }

            .queue-number.is-calling {
                color: var(--display-blue);
                transform: scale(1.04);
                animation: numberPop .9s ease;
            }

            .patient-name {
                position: relative;
                z-index: 1;
                max-width: 100%;
                margin-top: 4px;
                color: var(--display-ink);
                font-size: 35px;
                line-height: 1.15;
                font-weight: 900;
                word-break: break-word;
                border: 1px solid rgba(8, 127, 140, .12);
                border-radius: 8px;
                background: rgba(255, 255, 255, .78);
                padding: 10px 18px;
                box-shadow: 0 14px 30px rgba(16, 42, 47, .08);
            }

            .patient-name.is-updated,
            .meta-value.is-updated {
                animation: contentFlash .75s ease;
            }

            .call-footer {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                border-top: 1px solid var(--display-line);
                background: linear-gradient(180deg, #f8fbfb, #eefafa);
            }

            .call-meta {
                min-width: 0;
                display: grid;
                grid-template-columns: 46px minmax(0, 1fr);
                gap: 12px;
                align-items: center;
                padding: 18px 22px;
            }

            .call-meta + .call-meta {
                border-left: 1px solid var(--display-line);
            }

            .meta-icon {
                width: 46px;
                height: 46px;
                border-radius: 8px;
                display: grid;
                place-items: center;
                background: #e0f7f6;
                color: var(--display-teal);
                font-size: 24px;
                box-shadow: inset 0 0 0 1px rgba(8, 127, 140, .08);
            }

            .meta-label {
                color: var(--display-muted);
                font-size: 12px;
                font-weight: 800;
                text-transform: uppercase;
            }

            .meta-value {
                min-height: 28px;
                color: var(--display-ink);
                font-size: 22px;
                line-height: 1.12;
                font-weight: 900;
                word-break: break-word;
            }

            .side-panel {
                display: grid;
                grid-template-rows: auto minmax(0, 1fr);
                overflow: hidden;
                background: rgba(255, 255, 255, .97);
            }

            .side-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border-bottom: 1px solid var(--display-line);
                padding: 18px;
            }

            .side-title {
                margin: 0;
                color: var(--display-ink);
                font-size: 18px;
                font-weight: 900;
            }

            .live-badge {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border: 1px solid #bbf7d0;
                border-radius: 8px;
                background: #f0fdf4;
                color: #166534;
                padding: 7px 10px;
                font-size: 12px;
                font-weight: 900;
                transition: background .18s ease, color .18s ease, border-color .18s ease;
            }

            .live-badge::before {
                content: "";
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: var(--display-green);
                animation: livePulse 1.5s ease infinite;
            }

            .live-badge.is-connecting {
                border-color: #fde68a;
                background: #fffbeb;
                color: #92400e;
            }

            .live-badge.is-connecting::before {
                background: var(--display-amber);
            }

            .live-badge.is-disconnected {
                border-color: #fecaca;
                background: #fef2f2;
                color: #991b1b;
            }

            .live-badge.is-disconnected::before {
                background: var(--display-red);
            }

            .side-body {
                min-height: 0;
                display: grid;
                grid-template-rows: auto minmax(0, 1fr);
                gap: 14px;
                padding: 18px;
                overflow: hidden;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .stat-item {
                border: 1px solid var(--display-line);
                border-radius: 8px;
                background: linear-gradient(180deg, #ffffff, #f2fbfb);
                padding: 12px;
                transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
            }

            .stat-item:hover {
                transform: translateY(-2px);
                border-color: rgba(8, 127, 140, .24);
                box-shadow: 0 12px 26px rgba(16, 42, 47, .08);
            }

            .stat-value {
                color: var(--display-teal);
                font-size: 28px;
                line-height: 1;
                font-weight: 900;
            }

            .stat-label {
                margin-top: 5px;
                color: var(--display-muted);
                font-size: 12px;
                font-weight: 800;
            }

            .history-panel {
                min-height: 0;
                align-self: start;
                display: grid;
                grid-template-rows: auto auto;
                align-content: start;
                border: 1px solid var(--display-line);
                border-radius: 8px;
                background: #fff;
                overflow: hidden;
            }

            .history-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                border-bottom: 1px solid var(--display-line);
                padding: 12px 14px;
            }

            .history-head span {
                color: var(--display-muted);
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .history-list {
                min-height: 0;
                position: relative;
                overflow-x: hidden;
                overflow-y: hidden;
                height: var(--history-visible-height, auto);
                max-height: var(--history-visible-height, none);
                padding: 10px;
                scrollbar-width: thin;
                scrollbar-color: rgba(8, 127, 140, .42) transparent;
            }

            .history-list:not(.is-slide-active) {
                overflow-y: auto;
            }

            .history-list.is-slide-active::before,
            .history-list.is-slide-active::after {
                content: "";
                position: absolute;
                left: 0;
                right: 0;
                z-index: 2;
                height: 28px;
                pointer-events: none;
            }

            .history-list.is-slide-active::before {
                top: 0;
                background: linear-gradient(180deg, #fff, rgba(255, 255, 255, 0));
            }

            .history-list.is-slide-active::after {
                bottom: 0;
                background: linear-gradient(0deg, #fff, rgba(255, 255, 255, 0));
            }

            .history-track {
                display: grid;
                align-content: start;
                gap: 8px;
                will-change: transform;
            }

            .history-list.is-slide-active .history-track {
                animation: historySlide var(--history-slide-duration, 18s) ease-in-out infinite alternate;
            }

            .history-list.is-slide-active:hover .history-track,
            .history-list.is-slide-active:focus-within .history-track {
                animation-play-state: paused;
            }

            .history-list::-webkit-scrollbar {
                width: 8px;
            }

            .history-list::-webkit-scrollbar-track {
                background: transparent;
            }

            .history-list::-webkit-scrollbar-thumb {
                border-radius: 999px;
                background: rgba(8, 127, 140, .42);
            }

            .history-empty {
                display: grid;
                place-items: center;
                min-height: 210px;
                color: var(--display-muted);
                text-align: center;
                font-weight: 800;
            }

            .history-item {
                display: grid;
                grid-template-columns: 72px minmax(0, 1fr);
                gap: 10px;
                align-items: center;
                border: 1px solid #e6eeee;
                border-radius: 8px;
                background: #fbfdfd;
                padding: 9px;
                transition: transform .18s ease, border-color .18s ease, background .18s ease;
            }

            .history-item.is-latest {
                border-color: rgba(37, 99, 235, .24);
                background: #f8fbff;
                animation: slideIn .38s ease both;
            }

            .history-item:hover {
                transform: translateX(3px);
                border-color: rgba(8, 127, 140, .22);
            }

            .history-number {
                min-height: 54px;
                display: grid;
                place-items: center;
                border-radius: 8px;
                background: #e0f7f6;
                color: var(--display-teal);
                font-size: 24px;
                font-weight: 900;
            }

            .history-name {
                color: var(--display-ink);
                font-size: 15px;
                line-height: 1.2;
                font-weight: 900;
                word-break: break-word;
            }

            .history-meta {
                margin-top: 4px;
                color: var(--display-muted);
                font-size: 12px;
                font-weight: 700;
                word-break: break-word;
            }

            .service-strip {
                min-height: 122px;
                border: 1px solid var(--display-line);
                border-radius: 8px;
                background: linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(240, 253, 250, .94));
                box-shadow: var(--display-shadow);
                overflow: hidden;
            }

            .strip-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border-bottom: 1px solid var(--display-line);
                padding: 10px 14px;
            }

            .strip-head h2 {
                margin: 0;
                color: var(--display-ink);
                font-size: 15px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .strip-subtitle {
                color: var(--display-muted);
                font-size: 12px;
                font-weight: 800;
            }

            .customer-services-container {
                overflow: hidden;
                padding: 10px;
            }

            .customer-services {
                display: flex;
                gap: 10px;
                width: max-content;
                min-width: 100%;
            }

            .customer-services.scroll-active {
                animation: scrollAnimation 26s linear infinite;
            }

            .service-box {
                width: 300px;
                min-height: 78px;
                display: grid;
                grid-template-columns: 72px minmax(0, 1fr);
                gap: 10px;
                align-items: center;
                border: 1px solid #dbeafe;
                border-radius: 8px;
                background: linear-gradient(180deg, #f8fbff, #eef6ff);
                color: var(--display-ink);
                padding: 10px;
                box-shadow: 0 10px 24px rgba(37, 99, 235, .08);
                transition: transform .18s ease, border-color .18s ease;
            }

            .service-box.is-latest {
                border-color: rgba(37, 99, 235, .34);
            }

            .service-box:hover {
                transform: translateY(-2px);
            }

            .service-box.is-empty {
                width: 100%;
                display: grid;
                place-items: center;
                border-color: var(--display-line);
                background: #fbfdfd;
                color: var(--display-muted);
                font-size: 15px;
                font-weight: 800;
            }

            .service-number {
                min-height: 58px;
                display: grid;
                place-items: center;
                border-radius: 8px;
                background: #dbeafe;
                color: var(--display-blue);
                font-size: 26px;
                font-weight: 900;
            }

            .service-doctor,
            .service-poli,
            .service-patient {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .service-poli {
                color: var(--display-teal);
                font-size: 13px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .service-doctor {
                margin-top: 3px;
                color: var(--display-ink);
                font-size: 14px;
                font-weight: 900;
            }

            .service-patient {
                margin-top: 3px;
                color: var(--display-muted);
                font-size: 12px;
                font-weight: 700;
            }

            @keyframes livePulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(22, 163, 74, .28);
                }

                70% {
                    box-shadow: 0 0 0 8px rgba(22, 163, 74, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(22, 163, 74, 0);
                }
            }

            @keyframes ambientSweep {
                0% {
                    transform: translateX(-75%);
                }

                100% {
                    transform: translateX(75%);
                }
            }

            @keyframes headerSweep {
                0%,
                42% {
                    transform: translateX(-85%);
                }

                100% {
                    transform: translateX(85%);
                }
            }

            @keyframes standbyPulse {
                0%,
                100% {
                    background: rgba(255, 255, 255, .12);
                }

                50% {
                    background: rgba(255, 255, 255, .22);
                }
            }

            @keyframes ringPulse {
                0% {
                    opacity: .72;
                    transform: scale(.94);
                }

                100% {
                    opacity: 0;
                    transform: scale(1.12);
                }
            }

            @keyframes numberPop {
                0% {
                    transform: scale(.94);
                    filter: saturate(1);
                }

                45% {
                    transform: scale(1.08);
                    filter: saturate(1.28);
                }

                100% {
                    transform: scale(1.04);
                    filter: saturate(1);
                }
            }

            @keyframes contentFlash {
                0% {
                    background-color: rgba(219, 234, 254, .88);
                }

                100% {
                    background-color: rgba(255, 255, 255, .78);
                }
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-8px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes bump {
                0% {
                    transform: scale(1);
                }

                45% {
                    transform: scale(1.04);
                }

                100% {
                    transform: scale(1);
                }
            }

            @keyframes historySlide {
                from {
                    transform: translateY(0);
                }

                to {
                    transform: translateY(calc(-1 * var(--history-scroll-distance, 0px)));
                }
            }

            @keyframes scrollAnimation {
                from {
                    transform: translateX(0);
                }

                to {
                    transform: translateX(-50%);
                }
            }

            @media (max-width: 1100px) {
                body {
                    overflow: auto;
                }

                .display-shell {
                    min-height: 100vh;
                }

                .display-main {
                    grid-template-columns: 1fr;
                    overflow: visible;
                }

                .side-panel {
                    min-height: 420px;
                }

                .history-panel {
                    max-height: min(520px, 62vh);
                }
            }

            @media (max-width: 760px) {
                .display-shell {
                    padding: 10px;
                }

                .display-header {
                    grid-template-columns: 1fr;
                }

                .brand-group {
                    align-items: flex-start;
                }

                .brand-logo {
                    width: 54px;
                    height: 54px;
                    flex-basis: 54px;
                }

                .brand-title h1 {
                    font-size: 22px;
                }

                .header-tools {
                    justify-content: space-between;
                }

                .clock-box {
                    min-width: 136px;
                }

                .queue-number {
                    min-height: 106px;
                    font-size: 76px;
                }

                .patient-name {
                    font-size: 24px;
                }

                .call-footer,
                .stats {
                    grid-template-columns: 1fr;
                }

                .history-panel {
                    max-height: min(460px, 58vh);
                }

                .call-meta + .call-meta {
                    border-left: 0;
                    border-top: 1px solid var(--display-line);
                }

                .service-box {
                    width: 260px;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                *,
                *::before,
                *::after {
                    animation-duration: .01ms !important;
                    animation-iteration-count: 1 !important;
                    scroll-behavior: auto !important;
                    transition-duration: .01ms !important;
                }

                .history-list.is-slide-active {
                    overflow-y: auto;
                }

                .history-list.is-slide-active .history-track {
                    animation: none !important;
                    transform: none !important;
                }
            }
        </style>
    </head>

    <body>
        <main class="display-shell">
            <header class="display-header">
                <div class="brand-group">
                    <img src="{{ asset("plugins/img/logoarsy.png") }}" alt="Logo RS" class="brand-logo">
                    <div class="brand-title">
                        <h1>Antrean Poliklinik Rawat Jalan</h1>
                        <p>RS Abdurrahman Syamsyuri</p>
                    </div>
                </div>

                <div class="header-tools">
                    <div class="clock-box">
                        <div class="clock-time" id="clockTime">--:--</div>
                        <div class="clock-date" id="clockDate">-</div>
                    </div>

                    <div class="sound-state" id="audioStatus" role="button" tabindex="0" aria-label="Aktifkan audio panggilan">
                        <span class="sound-dot"></span>
                        <span id="audioStatusText">Klik layar aktifkan audio</span>
                    </div>

                    <button class="fullscreen-btn" id="fullscreen-btn" type="button" aria-label="Layar penuh" title="Layar penuh">
                        <i class="ri-fullscreen-line"></i>
                    </button>
                </div>
            </header>

            <section class="display-main">
                <section class="call-panel" aria-live="polite">
                    <div class="call-status">
                        <div class="call-status-label">
                            <i class="ri-notification-3-line"></i>
                            <span>Sedang Dipanggil</span>
                        </div>

                        <div class="queue-count">
                            <i class="ri-volume-up-line"></i>
                            <span id="pendingCount">0</span>
                            <span>antrean suara</span>
                        </div>
                    </div>

                    <div class="call-content">
                        <div class="call-stage">
                            <div class="call-eyebrow">Nomor antrean</div>
                            <div class="queue-number number" id="currentNumber">-</div>
                            <div class="patient-name name" id="currentPatient">Menunggu panggilan</div>
                        </div>
                    </div>

                    <div class="call-footer">
                        <div class="call-meta">
                            <div class="meta-icon">
                                <i class="ri-hospital-line"></i>
                            </div>
                            <div>
                                <div class="meta-label">Poliklinik</div>
                                <div class="meta-value poli" id="currentPoli">-</div>
                            </div>
                        </div>

                        <div class="call-meta">
                            <div class="meta-icon">
                                <i class="ri-stethoscope-line"></i>
                            </div>
                            <div>
                                <div class="meta-label">Dokter</div>
                                <div class="meta-value dokter" id="currentDoctor">-</div>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="side-panel">
                    <div class="side-head">
                        <h2 class="side-title">Monitor Panggilan</h2>
                        <span class="live-badge is-connecting" id="connectionState">Menghubungkan</span>
                    </div>

                    <div class="side-body">
                        <div class="stats">
                            <div class="stat-item">
                                <div class="stat-value" id="activePoliCount">0</div>
                                <div class="stat-label">Poli aktif</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value" id="calledCount">0</div>
                                <div class="stat-label">Panggilan tampil</div>
                            </div>
                        </div>

                        <div class="history-panel">
                            <div class="history-head">
                                <span>Riwayat Terakhir</span>
                                <span id="lastUpdated">Belum ada data</span>
                            </div>
                            <div class="history-list" id="historyList">
                                <div class="history-empty">
                                    Belum ada panggilan hari ini
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="service-strip">
                <div class="strip-head">
                    <h2>Poli dan Dokter Terakhir Dipanggil</h2>
                    <div class="strip-subtitle">Nomor terbaru per layanan</div>
                </div>
                <div class="customer-services-container">
                    <div class="customer-services" id="customer-services">
                        <div class="service-box is-empty">
                            Menunggu data panggilan poliklinik
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
        <script src="{{ asset("assets/vendors/core/core.js") }}"></script>
        <script src="{{ asset("assets/vendors/feather-icons/feather.min.js") }}"></script>
        <script src="{{ asset("assets/js/template.js") }}"></script>

        <script>
            $(document).ready(function() {
                const pusher = new Pusher("{{ env("PUSHER_APP_KEY") }}", {
                    cluster: "{{ env("PUSHER_APP_CLUSTER") }}",
                    forceTLS: false
                });

                const channel = pusher.subscribe("panggilan-pasien-V2");
                let lastQueueNumber = null;
                let queueList = [];
                let isSpeaking = false;
                let calledQueueList = [];
                let isUserInteracted = false;
                let historyResizeTimer = null;

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function setTextWithBump(selector, value) {
                    const nextValue = String(value);
                    const element = $(selector);

                    if (element.text() !== nextValue) {
                        element.text(nextValue);
                        const bumpTarget = element.closest('.stat-item, .queue-count');
                        bumpTarget.addClass('is-bumped');

                        window.setTimeout(function() {
                            bumpTarget.removeClass('is-bumped');
                        }, 380);
                    }
                }

                function updateClock() {
                    const now = new Date();
                    $('#clockTime').text(now.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    }));
                    $('#clockDate').text(now.toLocaleDateString('id-ID', {
                        weekday: 'long',
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    }));
                }

                function updateCounters() {
                    const serviceMap = getServiceMap();
                    setTextWithBump('#pendingCount', queueList.length);
                    setTextWithBump('#activePoliCount', serviceMap.size);
                    setTextWithBump('#calledCount', calledQueueList.length);
                    $('#pendingCount').closest('.queue-count').toggleClass('has-pending', queueList.length > 0);
                }

                function updateConnectionState(state) {
                    const states = {
                        connected: {
                            label: 'Live',
                            className: 'is-connected'
                        },
                        connecting: {
                            label: 'Menghubungkan',
                            className: 'is-connecting'
                        },
                        unavailable: {
                            label: 'Gangguan',
                            className: 'is-disconnected'
                        },
                        failed: {
                            label: 'Gagal',
                            className: 'is-disconnected'
                        },
                        disconnected: {
                            label: 'Terputus',
                            className: 'is-disconnected'
                        }
                    };
                    const config = states[state] || states.connecting;

                    $('#connectionState')
                        .removeClass('is-connected is-connecting is-disconnected')
                        .addClass(config.className)
                        .text(config.label);
                }

                function setAudioReady() {
                    if (isUserInteracted) return;

                    isUserInteracted = true;
                    $('#audioStatus').addClass('is-ready');
                    $('#audioStatus').attr('aria-label', 'Audio panggilan aktif');
                    $('#audioStatusText').text('Audio aktif');

                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2400,
                        timerProgressBar: true
                    });

                    Toast.fire({
                        icon: "success",
                        title: "Display siap menerima panggilan"
                    });

                    processQueue();
                }

                pusher.connection.bind('state_change', function(states) {
                    updateConnectionState(states.current);
                });
                pusher.connection.bind('error', function() {
                    updateConnectionState('unavailable');
                });
                updateConnectionState(pusher.connection.state);

                $('#audioStatus').on('click keydown', function(event) {
                    if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;

                    event.preventDefault();
                    setAudioReady();
                });

                function syncFullscreenButton() {
                    const isFullscreen = Boolean(document.fullscreenElement);
                    $('#fullscreen-btn')
                        .attr('aria-label', isFullscreen ? 'Keluar layar penuh' : 'Layar penuh')
                        .attr('title', isFullscreen ? 'Keluar layar penuh' : 'Layar penuh');
                    $('#fullscreen-btn i')
                        .toggleClass('ri-fullscreen-line', !isFullscreen)
                        .toggleClass('ri-fullscreen-exit-line', isFullscreen);
                }

                $('#fullscreen-btn').on('click', function() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen();
                    } else {
                        document.exitFullscreen();
                    }
                });
                document.addEventListener('fullscreenchange', syncFullscreenButton);
                syncFullscreenButton();

                document.addEventListener("click", setAudioReady, {
                    once: true
                });
                document.addEventListener("touchstart", setAudioReady, {
                    once: true
                });
                window.addEventListener('resize', function() {
                    window.clearTimeout(historyResizeTimer);
                    historyResizeTimer = window.setTimeout(refreshHistorySlide, 150);
                });

                channel.bind("PanggilPasien-V2", function(data) {
                    if (data && data.nomorAntrian && data.dokter && data.poli && data.nama) {
                        let formattedData = {
                            no_reg: data.nomorAntrian,
                            nm_dokter: data.dokter,
                            nm_poli: data.poli,
                            nm_pasien: data.nama,
                            called_at: new Date()
                        };

                        if (!queueList.some(q => q.no_reg === formattedData.no_reg)) {
                            queueList.push(formattedData);
                            calledQueueList.unshift(formattedData);
                            calledQueueList = calledQueueList.slice(0, 20);
                            updateCustomerServices();
                            updateHistory();
                            updateCounters();
                            processQueue();
                        }
                    } else {
                        console.error("Data tidak valid setelah konversi:", data);
                    }
                });

                function processQueue() {
                    if (!isUserInteracted) return;
                    if (isSpeaking || queueList.length === 0) return;

                    isSpeaking = true;
                    let row = queueList.shift();
                    lastQueueNumber = row.no_reg;
                    updateCounters();
                    updateDisplay(row);
                    speakQueue(row.nm_dokter, row.nm_poli, row.no_reg, row.nm_pasien);
                }

                function updateDisplay(row) {
                    $("#currentDoctor, .queue-box .dokter").text(row.nm_dokter || "-");
                    $("#currentPoli, .queue-box .poli").text(row.nm_poli || "-");
                    $("#currentNumber, .queue-box .number").text(row.no_reg || "-");
                    $("#currentPatient, .queue-box .name").text(row.nm_pasien || "-");
                    $('#lastUpdated').text(new Date().toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    }));

                    $('.call-panel').addClass('is-speaking');
                    $('#currentNumber').addClass('is-calling');
                    $('#currentPatient, #currentPoli, #currentDoctor').addClass('is-updated');

                    window.setTimeout(function() {
                        $('#currentNumber').removeClass('is-calling');
                    }, 900);
                    window.setTimeout(function() {
                        $('#currentPatient, #currentPoli, #currentDoctor').removeClass('is-updated');
                    }, 760);
                }

                function formatPronunciation(dokter) {
                    let replacements = {
                        "Sp. PD": "espe, pede",
                        "Sp. A": "espe, a",
                        "Sp. B": "espe, be",
                        "Sp. KK": "espe, ka, ka",
                        "dr.": "dokter",
                        "drg.": "dokter gigi"
                    };

                    for (let key in replacements) {
                        dokter = dokter.replace(new RegExp(key, "gi"), replacements[key]);
                    }

                    return dokter;
                }

                function formatPatientName(pasien) {
                    let replacements = {
                        "Tn": "Tuan",
                        "Ny": "Nyonya",
                        "An": "Anak",
                        "Nn": "Nona",
                        "Sdr": "Saudara"
                    };

                    for (let key in replacements) {
                        pasien = pasien.replace(new RegExp(`\\b${key}\\.?\\b`, "gi"), replacements[key]);
                    }

                    let match = pasien.match(/(.*)[,\.]\s*(Tuan|Nyonya|Anak|Nona|Saudara)/i);
                    if (match) {
                        pasien = `${match[2]}. ${match[1]}`;
                    }

                    pasien = pasien.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());

                    return pasien;
                }

                function finishSpeech() {
                    isSpeaking = false;
                    $('.call-panel').removeClass('is-speaking');
                    updateCounters();
                    processQueue();
                }

                function playSpeech(text) {
                    let speech = new SpeechSynthesisUtterance(text);
                    speech.lang = "id-ID";
                    speech.rate = 0.82;
                    speech.pitch = 1;
                    speech.onend = finishSpeech;
                    speech.onerror = finishSpeech;

                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(speech);
                }

                function speakQueue(dokter, poli, nomor, pasien) {
                    if ('speechSynthesis' in window) {
                        let formattedDokter = formatPronunciation(dokter || '');
                        let formattedPasien = formatPatientName(pasien || '');
                        let text =
                            `Nomor antrian: ${nomor}, atas nama: ${formattedPasien}, di: ${poli}, ${formattedDokter}`;
                        let bell = new Audio("{{ asset("plugins/audio/Airport_Bell.mp3") }}");
                        let speechStarted = false;

                        function startSpeech() {
                            if (speechStarted) return;

                            speechStarted = true;
                            playSpeech(text);
                        }

                        bell.onended = startSpeech;
                        bell.onerror = startSpeech;

                        let bellPlay = bell.play();
                        if (bellPlay && typeof bellPlay.catch === 'function') {
                            bellPlay.catch(startSpeech);
                        }
                    } else {
                        console.error("Browser tidak mendukung Text-to-Speech.");
                        finishSpeech();
                    }
                }

                function getServiceMap() {
                    let serviceMap = new Map();

                    calledQueueList.forEach(item => {
                        let key = `${item.nm_poli}-${item.nm_dokter}`;

                        if (!serviceMap.has(key)) {
                            serviceMap.set(key, {
                                ...item
                            });
                        }
                    });

                    return serviceMap;
                }

                function refreshHistorySlide() {
                    const list = document.getElementById('historyList');
                    if (!list) return;

                    list.classList.remove('is-slide-active');
                    list.scrollTop = 0;
                    list.style.removeProperty('--history-visible-height');
                    list.style.removeProperty('--history-scroll-distance');
                    list.style.removeProperty('--history-slide-duration');

                    window.requestAnimationFrame(function() {
                        const track = list.querySelector('.history-track');
                        if (!track) return;

                        const items = Array.from(track.children);
                        if (!items.length) return;

                        const listStyle = window.getComputedStyle(list);
                        const trackStyle = window.getComputedStyle(track);
                        const paddingTop = parseFloat(listStyle.paddingTop) || 0;
                        const paddingBottom = parseFloat(listStyle.paddingBottom) || 0;
                        const rowGap = parseFloat(trackStyle.rowGap) || parseFloat(trackStyle.gap) || 0;
                        const visibleCount = Math.min(3, items.length);
                        const visibleItemsHeight = items.slice(0, visibleCount).reduce((total, item) => {
                            return total + item.offsetHeight;
                        }, 0);
                        const visibleHeight = Math.ceil(
                            visibleItemsHeight + (Math.max(0, visibleCount - 1) * rowGap) + paddingTop + paddingBottom
                        );

                        list.style.setProperty('--history-visible-height', `${visibleHeight}px`);
                        if (items.length <= 3) return;

                        const viewportHeight = Math.max(0, visibleHeight - paddingTop - paddingBottom);
                        const distance = Math.max(0, track.scrollHeight - viewportHeight);
                        if (distance <= 6) return;

                        const duration = Math.min(42, Math.max(16, Math.round(distance / 9)));
                        list.style.setProperty('--history-scroll-distance', `${distance}px`);
                        list.style.setProperty('--history-slide-duration', `${duration}s`);
                        list.classList.add('is-slide-active');
                    });
                }

                function updateHistory() {
                    let history = calledQueueList;

                    if (!history.length) {
                        $('#historyList').html(`
                            <div class="history-empty">
                                Belum ada panggilan hari ini
                            </div>
                        `);
                        refreshHistorySlide();
                        return;
                    }

                    let items = history.map((item, index) => {
                        return `
                            <div class="history-item${index === 0 ? ' is-latest' : ''}">
                                <div class="history-number">${escapeHtml(item.no_reg || '-')}</div>
                                <div>
                                    <div class="history-name">${escapeHtml(item.nm_pasien || '-')}</div>
                                    <div class="history-meta">
                                        ${escapeHtml(item.nm_poli || '-')} / ${escapeHtml(item.nm_dokter || '-')}
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');

                    let html = `<div class="history-track">${items}</div>`;
                    $('#historyList').html(html);
                    refreshHistorySlide();
                }

                function updateCustomerServices() {
                    let container = $('#customer-services');
                    let serviceMap = getServiceMap();

                    if (serviceMap.size === 0) {
                        container.removeClass("scroll-active");
                        container.html(`
                            <div class="service-box is-empty">
                                Menunggu data panggilan poliklinik
                            </div>
                        `);
                        updateCounters();
                        return;
                    }

                    let serviceBoxes = Array.from(serviceMap.values()).map((item, index) => {
                        return `
                            <div class="service-box${index === 0 ? ' is-latest' : ''}">
                                <div class="service-number">${escapeHtml(item.no_reg || '-')}</div>
                                <div>
                                    <div class="service-poli">${escapeHtml(item.nm_poli || '-')}</div>
                                    <div class="service-doctor">${escapeHtml(item.nm_dokter || '-')}</div>
                                    <div class="service-patient">${escapeHtml(item.nm_pasien || '-')}</div>
                                </div>
                            </div>
                        `;
                    }).join('');

                    if (serviceMap.size >= 4) {
                        container.html(serviceBoxes + serviceBoxes);
                        container.addClass("scroll-active");
                    } else {
                        container.html(serviceBoxes);
                        container.removeClass("scroll-active");
                    }

                    updateCounters();
                }

                updateClock();
                window.setInterval(updateClock, 1000);
                updateCustomerServices();
                updateHistory();
                updateCounters();
            });
        </script>
    </body>

</html>
