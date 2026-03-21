<?php

return [

    'neon' => [
        'nombre' => 'Neón',
        'descripcion' => 'Oscuro y vibrante',
        'preview' => ['#120d02', '#00d4e8', '#c19849'],
        'vars' => [
            // Colores de acento
            '--mg-gold'              => '#c19849',
            '--mg-gold-glow'         => 'rgba(193,152,73,0.5)',
            '--mg-gray'              => '#535353',
            '--mg-neon-cyan'         => '#00d4e8',
            '--mg-neon-cyan-glow'    => 'rgba(0,212,232,0.25)',
            '--mg-neon-lime'         => '#c8df00',
            '--mg-neon-lime-glow'    => 'rgba(200,223,0,0.35)',

            // Fondos
            '--mg-body-bg'           => '#120d02',
            '--mg-card-bg'           => '#1a1306',
            '--mg-input-bg'          => '#201808',
            '--mg-header-bg'         => '#0d0901',

            // Texto y bordes
            '--mg-text'              => '#e8dcc8',
            '--mg-text-muted'        => '#9a8870',
            '--mg-border'            => 'rgba(193,152,73,0.22)',
            '--mg-input-border'      => 'rgba(193,152,73,0.25)',

            // Botón primario
            '--mg-btn-primary-bg'    => '#c19849',
            '--mg-btn-primary-text'  => '#0d0901',
            '--mg-btn-primary-hover' => '#d4a955',

            // === IDENTIDAD VISUAL ===

            // Fondo con gradiente ambiental
            '--mg-body-gradient'     => 'radial-gradient(ellipse at 20% 0%, rgba(0,212,232,0.04) 0%, transparent 60%), radial-gradient(ellipse at 80% 100%, rgba(200,223,0,0.04) 0%, transparent 60%)',

            // Formas — esquinas afiladas-medias
            '--mg-radius-card'       => '14px',
            '--mg-radius-chip'       => '10px',
            '--mg-radius-btn'        => '8px',
            '--mg-radius-input'      => '6px',
            '--mg-radius-modal'      => '14px',

            // Sombras estilo neón glow
            '--mg-shadow-card'       => '0 0 20px rgba(0,212,232,0.12), 0 8px 32px rgba(0,0,0,0.5)',
            '--mg-shadow-hover'      => '0 0 18px rgba(0,212,232,0.28), 0 4px 16px rgba(0,0,0,0.5)',
            '--mg-shadow-modal'      => '0 0 30px rgba(0,212,232,0.22), 0 8px 40px rgba(0,0,0,0.6)',

            // Header
            '--mg-header-border'     => '2px solid #00d4e8',
            '--mg-header-shadow'     => '0 2px 16px rgba(0,212,232,0.22)',

            // Tipografía
            '--mg-font-family'       => "'Source Sans Pro', system-ui, sans-serif",
            '--mg-title-shadow'      => '0 0 16px rgba(193,152,73,0.5)',
            '--mg-title-weight'      => '700',
            '--mg-letter-spacing'    => '0em',

            // Interacciones
            '--mg-hover-transform'   => 'translateY(-2px)',
            '--mg-chip-active-bg'    => 'rgba(0,212,232,0.12)',
            '--mg-chip-active-shadow'=> '0 0 12px rgba(0,212,232,0.22), inset 0 0 8px rgba(0,212,232,0.08)',

            // Franjas de horario
            '--mg-franja-border'     => 'rgba(200,223,0,0.3)',
            '--mg-franja-hover-shadow'=> '0 0 14px rgba(200,223,0,0.35), 0 4px 12px rgba(0,0,0,0.4)',
        ],
    ],

    'clasico' => [
        'nombre' => 'Clásico',
        'descripcion' => 'Limpio y profesional',
        'preview' => ['#f7fafc', '#2c5282', '#e2883c'],
        'vars' => [
            // Colores de acento
            '--mg-gold'              => '#2c5282',
            '--mg-gold-glow'         => 'rgba(44,82,130,0.25)',
            '--mg-gray'              => '#718096',
            '--mg-neon-cyan'         => '#3182ce',
            '--mg-neon-cyan-glow'    => 'rgba(49,130,206,0.18)',
            '--mg-neon-lime'         => '#38a169',
            '--mg-neon-lime-glow'    => 'rgba(56,161,105,0.2)',

            // Fondos
            '--mg-body-bg'           => '#f7fafc',
            '--mg-card-bg'           => '#ffffff',
            '--mg-input-bg'          => '#ffffff',
            '--mg-header-bg'         => '#2c5282',

            // Texto y bordes
            '--mg-text'              => '#1a202c',
            '--mg-text-muted'        => '#718096',
            '--mg-border'            => '#e2e8f0',
            '--mg-input-border'      => '#cbd5e0',

            // Botón primario
            '--mg-btn-primary-bg'    => '#2c5282',
            '--mg-btn-primary-text'  => '#ffffff',
            '--mg-btn-primary-hover' => '#2a4a7f',

            // === IDENTIDAD VISUAL ===

            // Sin gradiente — fondo liso y limpio
            '--mg-body-gradient'     => 'none',

            // Formas — esquinas cuadradas/rectas, estilo corporativo
            '--mg-radius-card'       => '4px',
            '--mg-radius-chip'       => '3px',
            '--mg-radius-btn'        => '4px',
            '--mg-radius-input'      => '4px',
            '--mg-radius-modal'      => '6px',

            // Sombras estilo elevación, sin brillo
            '--mg-shadow-card'       => '0 1px 3px rgba(0,0,0,0.08), 0 4px 16px rgba(44,82,130,0.08)',
            '--mg-shadow-hover'      => '0 6px 24px rgba(44,82,130,0.16)',
            '--mg-shadow-modal'      => '0 8px 32px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.08)',

            // Header — sombra de elevación, sin borde neón
            '--mg-header-border'     => 'none',
            '--mg-header-shadow'     => '0 2px 8px rgba(0,0,0,0.2)',

            // Tipografía — serif, aspecto formal
            '--mg-font-family'       => "Georgia, 'Times New Roman', serif",
            '--mg-title-shadow'      => 'none',
            '--mg-title-weight'      => '700',
            '--mg-letter-spacing'    => '0.01em',

            // Interacciones — lift sin brillo
            '--mg-hover-transform'   => 'translateY(-3px)',
            '--mg-chip-active-bg'    => 'rgba(49,130,206,0.1)',
            '--mg-chip-active-shadow'=> '0 2px 8px rgba(49,130,206,0.2)',

            // Franjas de horario
            '--mg-franja-border'     => 'rgba(56,161,105,0.4)',
            '--mg-franja-hover-shadow'=> '0 6px 20px rgba(56,161,105,0.18)',
        ],
    ],

    'pastel' => [
        'nombre' => 'Pastel',
        'descripcion' => 'Suave y acogedor',
        'preview' => ['#fdf4ff', '#8b5cf6', '#f472b6'],
        'vars' => [
            // Colores de acento
            '--mg-gold'              => '#8b5cf6',
            '--mg-gold-glow'         => 'rgba(139,92,246,0.3)',
            '--mg-gray'              => '#a78bfa',
            '--mg-neon-cyan'         => '#f472b6',
            '--mg-neon-cyan-glow'    => 'rgba(244,114,182,0.2)',
            '--mg-neon-lime'         => '#34d399',
            '--mg-neon-lime-glow'    => 'rgba(52,211,153,0.3)',

            // Fondos
            '--mg-body-bg'           => '#fdf4ff',
            '--mg-card-bg'           => '#ffffff',
            '--mg-input-bg'          => '#fdf4ff',
            '--mg-header-bg'         => '#7c3aed',

            // Texto y bordes
            '--mg-text'              => '#4c1d95',
            '--mg-text-muted'        => '#7c3aed',
            '--mg-border'            => '#ddd6fe',
            '--mg-input-border'      => '#c4b5fd',

            // Botón primario
            '--mg-btn-primary-bg'    => '#8b5cf6',
            '--mg-btn-primary-text'  => '#ffffff',
            '--mg-btn-primary-hover' => '#7c3aed',

            // === IDENTIDAD VISUAL ===

            // Gradiente suave de burbujas pastel
            '--mg-body-gradient'     => 'radial-gradient(ellipse at 10% 10%, rgba(139,92,246,0.1) 0%, transparent 50%), radial-gradient(ellipse at 90% 90%, rgba(244,114,182,0.12) 0%, transparent 50%)',

            // Formas — muy redondeadas, estilo bubbly
            '--mg-radius-card'       => '20px',
            '--mg-radius-chip'       => '99px',
            '--mg-radius-btn'        => '99px',
            '--mg-radius-input'      => '12px',
            '--mg-radius-modal'      => '24px',

            // Sombras suaves difusas, sin neon
            '--mg-shadow-card'       => '0 4px 24px rgba(139,92,246,0.14)',
            '--mg-shadow-hover'      => '0 8px 32px rgba(139,92,246,0.22)',
            '--mg-shadow-modal'      => '0 12px 48px rgba(139,92,246,0.2)',

            // Header — sin borde, solo sombra suave
            '--mg-header-border'     => 'none',
            '--mg-header-shadow'     => '0 4px 16px rgba(139,92,246,0.15)',

            // Tipografía — redondeada y amigable
            '--mg-font-family'       => "'Trebuchet MS', system-ui, sans-serif",
            '--mg-title-shadow'      => 'none',
            '--mg-title-weight'      => '800',
            '--mg-letter-spacing'    => '-0.01em',

            // Interacciones — escala suave tipo "pop"
            '--mg-hover-transform'   => 'scale(1.03)',
            '--mg-chip-active-bg'    => 'rgba(244,114,182,0.15)',
            '--mg-chip-active-shadow'=> '0 4px 16px rgba(244,114,182,0.25)',

            // Franjas de horario
            '--mg-franja-border'     => 'rgba(52,211,153,0.5)',
            '--mg-franja-hover-shadow'=> '0 8px 24px rgba(52,211,153,0.2)',
        ],
    ],

];
