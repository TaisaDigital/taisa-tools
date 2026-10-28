<?php
// Custom maintenance template (styled with background image and footer text)
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Text variables (could be moved to UI later)
$taisa_maintenance_title       = get_bloginfo('name');
$taisa_maintenance_heading     = 'Trabajando en la Web ✨';
$taisa_maintenance_message     = 'Estoy creando algo muy especial aquí. Pronto os contaré más.';
$taisa_maintenance_footer_note = 'El equipo de TaisaDigital está añadiendo magia ✨ en esta web.';

// Background 

// Imagen de fondo fija dentro del plugin
$bg_url = esc_url( trailingslashit( plugin_dir_url(__FILE__) ) . '../assets/maintenance-default.webp' );


?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo esc_html( $taisa_maintenance_title ); ?> – Mantenimiento</title>
    <style>
        :root{
            --overlay: rgba(12, 18, 46, 0.55);
            --card-bg: #ffffff;
            --accent: #f03f9e;
            --accent-2: #2b68a6;
            --text: #0f172a;
            --muted: #6b7280;
            --shadow: 0 20px 60px rgba(0,0,0,.25);
        }
        *{box-sizing:border-box}
        html,body{height:100%;margin:0}
        body{
            min-height:100vh;
            background-image: linear-gradient(0deg, var(--overlay), var(--overlay)), url('<?php echo esc_attr( $bg_url ); ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display:flex;
            flex-direction:column;
        }
        .center{
            flex:1;
            display:grid;
            place-items:center;
            padding:24px;
        }
        .card{
            width:min(92vw, 720px);
            background:var(--card-bg);
            border-radius:20px;
            box-shadow:var(--shadow);
            padding:32px 28px;
            text-align:center;
        }
        h1{margin:0 0 10px;font-size: clamp(22px, 3.2vw, 32px);color:var(--accent-2);font-family: "Poppins","Segoe UI",system-ui,sans-serif}
        p{margin:0;color:var(--text);font-family: "Poppins","Segoe UI",system-ui,sans-serif;line-height:1.6}
        .muted{color:var(--muted);margin-top:8px}
        .footer{
            width:100%;
            padding:12px 16px;
            text-align:center;
            color:#fff;
            font-family: "Poppins","Segoe UI",system-ui,sans-serif;
            background: linear-gradient(90deg, var(--accent-2), var(--accent));
        }
        @media (max-width: 480px){
            .card{padding:24px 18px}
        }
    </style>
</head>
<body>
    <div class="center">
        <div class="card">
            <h1><?php echo esc_html( $taisa_maintenance_heading ); ?></h1>
            <p><?php echo esc_html( $taisa_maintenance_message ); ?></p>
            <p class="muted"><?php echo esc_html( get_bloginfo('name') ); ?></p>
        </div>
    </div>
    <div class="footer">
        <?php echo esc_html( $taisa_maintenance_footer_note ); ?>
    </div>
</body>
</html>
