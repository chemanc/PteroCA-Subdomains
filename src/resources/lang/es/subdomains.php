<?php

/**
 * PteroCA Subdomains - Traducciones al Español
 * 
 * @package PteroCA Subdomains
 * @author  XMA Corporation
 * @license MIT
 */

return [
    // ============================================
    // GENERAL
    // ============================================
    'title' => 'Gestor de Subdominios',
    'subdomain' => 'Subdominio',
    'subdomains' => 'Subdominios',
    'domain' => 'Dominio',
    'status' => 'Estado',
    'actions' => 'Acciones',
    'save' => 'Guardar',
    'cancel' => 'Cancelar',
    'delete' => 'Eliminar',
    'edit' => 'Editar',
    'create' => 'Crear',
    'update' => 'Actualizar',
    'search' => 'Buscar',
    'filter' => 'Filtrar',
    'export' => 'Exportar',
    'import' => 'Importar',
    'refresh' => 'Actualizar',
    'loading' => 'Cargando...',
    'no_results' => 'No se encontraron resultados',
    'confirm' => 'Confirmar',
    'yes' => 'Sí',
    'no' => 'No',

    // ============================================
    // INTERFAZ DE USUARIO (Área del Cliente)
    // ============================================
    'your_subdomain' => 'Tu Subdominio',
    'no_subdomain' => 'No hay subdominio configurado para este servidor',
    'no_subdomain_hint' => 'Crea un subdominio personalizado para que los jugadores puedan conectarse fácilmente a tu servidor.',
    'create_subdomain' => 'Crear Subdominio',
    'change_subdomain' => 'Cambiar Subdominio',
    'delete_subdomain' => 'Eliminar Subdominio',
    'subdomain_placeholder' => 'Ingresa el subdominio deseado',
    'subdomain_preview' => 'La dirección de tu servidor será:',
    'connect_using' => 'Conectar usando',
    'copy_address' => 'Copiar Dirección',
    'copied' => '¡Copiado!',
    'copy_failed' => 'Error al copiar',
    'server_address' => 'Dirección del Servidor',
    'port' => 'Puerto',
    'full_address' => 'Dirección Completa',

    // ============================================
    // ESTADO
    // ============================================
    'status_pending' => 'Pendiente',
    'status_active' => 'Activo',
    'status_suspended' => 'Suspendido',
    'status_error' => 'Error',
    'dns_propagating' => 'DNS propagándose (puede tomar hasta 5 minutos)',
    'dns_active' => 'Los registros DNS están activos',
    'dns_suspended' => 'Los registros DNS están suspendidos',
    'dns_error' => 'Hubo un error con los registros DNS',

    // ============================================
    // MENSAJES DE VALIDACIÓN
    // ============================================
    'subdomain_available' => '¡El subdominio está disponible!',
    'subdomain_taken' => 'Este subdominio ya está en uso',
    'subdomain_taken_cloudflare' => 'Este subdominio ya existe en DNS',
    'subdomain_blacklisted' => 'Este subdominio no está permitido',
    'subdomain_invalid' => 'Formato de subdominio inválido. Usa solo letras, números y guiones.',
    'subdomain_invalid_start' => 'El subdominio no puede comenzar con un guion',
    'subdomain_invalid_end' => 'El subdominio no puede terminar con un guion',
    'subdomain_invalid_consecutive' => 'El subdominio no puede tener guiones consecutivos',
    'subdomain_too_short' => 'El subdominio debe tener al menos :min caracteres',
    'subdomain_too_long' => 'El subdominio no puede exceder :max caracteres',
    'cooldown_active' => 'Podrás cambiar tu subdominio en :time',
    'cooldown_hours' => ':hours horas',
    'cooldown_minutes' => ':minutes minutos',
    'checking_availability' => 'Verificando disponibilidad...',

    // ============================================
    // MENSAJES DE ÉXITO
    // ============================================
    'subdomain_created' => '¡Subdominio creado exitosamente! El DNS puede tardar unos minutos en propagarse.',
    'subdomain_updated' => '¡Subdominio actualizado exitosamente! El DNS puede tardar unos minutos en propagarse.',
    'subdomain_deleted' => 'Subdominio eliminado exitosamente.',
    'settings_saved' => 'Configuración guardada exitosamente.',
    'blacklist_added' => 'Palabra agregada a la lista negra.',
    'blacklist_removed' => 'Palabra eliminada de la lista negra.',
    'blacklist_imported' => ':count palabras importadas a la lista negra.',
    'default_blacklist_loaded' => 'Lista negra predeterminada cargada exitosamente.',
    'dns_synced' => 'Registros DNS sincronizados exitosamente.',
    'connection_test_success' => '¡Conexión a Cloudflare exitosa!',

    // ============================================
    // MENSAJES DE ERROR
    // ============================================
    'cloudflare_error' => 'Error del proveedor DNS. Por favor intenta más tarde.',
    'cloudflare_connection_failed' => 'Error al conectar con Cloudflare: :error',
    'cloudflare_record_failed' => 'Error al crear registro DNS: :error',
    'server_not_found' => 'Servidor no encontrado',
    'permission_denied' => 'No tienes permiso para gestionar este subdominio',
    'already_has_subdomain' => 'Este servidor ya tiene un subdominio',
    'feature_disabled' => 'La función de subdominios está deshabilitada actualmente',
    'invalid_domain' => 'Dominio seleccionado inválido',
    'domain_not_configured' => 'No hay dominios configurados. Por favor contacta al administrador.',
    'api_not_configured' => 'La API de Cloudflare no está configurada. Por favor contacta al administrador.',
    'generic_error' => 'Ocurrió un error. Por favor intenta más tarde.',
    'import_failed' => 'Error al importar lista negra: :error',
    'export_failed' => 'Error al exportar datos: :error',

    // ============================================
    // INTERFAZ DE ADMINISTRACIÓN
    // ============================================
    'admin_title' => 'Gestión de Subdominios',
    'admin_description' => 'Administra subdominios personalizados para servidores de juegos',
    'dashboard' => 'Panel',
    'settings' => 'Configuración',
    'blacklist' => 'Lista Negra',
    'logs' => 'Registro de Actividad',
    'domains' => 'Dominios',
    'statistics' => 'Estadísticas',

    // Estadísticas
    'total_subdomains' => 'Total de Subdominios',
    'active_subdomains' => 'Subdominios Activos',
    'pending_subdomains' => 'Subdominios Pendientes',
    'suspended_subdomains' => 'Subdominios Suspendidos',
    'error_subdomains' => 'Subdominios con Error',
    'subdomains_today' => 'Creados Hoy',
    'subdomains_this_week' => 'Creados Esta Semana',
    'subdomains_this_month' => 'Creados Este Mes',

    // ============================================
    // CONFIGURACIÓN DE ADMINISTRACIÓN
    // ============================================
    'cloudflare_settings' => 'Configuración de Cloudflare',
    'api_token' => 'Token de API',
    'api_token_placeholder' => 'Ingresa tu token de API de Cloudflare',
    'api_token_help' => 'Crea un token con permisos "Edit zone DNS" en Panel de Cloudflare > Mi Perfil > Tokens de API',
    'zone_id' => 'Zone ID',
    'zone_id_placeholder' => 'Ingresa tu Zone ID de Cloudflare',
    'zone_id_help' => 'Se encuentra en la página de Resumen de tu dominio en el Panel de Cloudflare (barra lateral derecha)',
    'test_connection' => 'Probar Conexión',
    'testing_connection' => 'Probando conexión...',
    'connection_status' => 'Estado de Conexión',
    'connected' => 'Conectado',
    'not_connected' => 'No Conectado',
    'last_tested' => 'Última prueba: :time',

    'subdomain_settings' => 'Configuración de Subdominios',
    'min_length' => 'Longitud Mínima',
    'min_length_help' => 'Número mínimo de caracteres para subdominios',
    'max_length' => 'Longitud Máxima',
    'max_length_help' => 'Número máximo de caracteres para subdominios',
    'change_cooldown' => 'Tiempo de Espera para Cambios',
    'change_cooldown_help' => 'Horas que los usuarios deben esperar antes de cambiar su subdominio nuevamente (0 = sin espera)',
    'hours' => 'horas',
    'auto_delete' => 'Auto-eliminar al Terminar Servidor',
    'auto_delete_help' => 'Eliminar automáticamente los registros DNS cuando se termina un servidor',
    'auto_suspend' => 'Auto-suspender al Suspender Servidor',
    'auto_suspend_help' => 'Deshabilitar automáticamente los registros DNS cuando se suspende un servidor',
    'default_ttl' => 'TTL Predeterminado',
    'default_ttl_help' => 'Tiempo de vida para los registros DNS',
    'ttl_auto' => 'Automático',
    'ttl_1min' => '1 minuto',
    'ttl_5min' => '5 minutos',
    'ttl_30min' => '30 minutos',
    'ttl_1hour' => '1 hora',
    'ttl_12hours' => '12 horas',
    'ttl_1day' => '1 día',

    'enabled' => 'Habilitado',
    'disabled' => 'Deshabilitado',

    // ============================================
    // GESTIÓN DE DOMINIOS
    // ============================================
    'domain_management' => 'Gestión de Dominios',
    'add_domain' => 'Agregar Dominio',
    'edit_domain' => 'Editar Dominio',
    'delete_domain' => 'Eliminar Dominio',
    'domain_name' => 'Nombre de Dominio',
    'domain_name_placeholder' => 'ejemplo.com',
    'cloudflare_zone' => 'Zone ID de Cloudflare',
    'is_default' => 'Dominio Predeterminado',
    'is_active' => 'Activo',
    'no_domains' => 'No hay dominios configurados',
    'domain_added' => 'Dominio agregado exitosamente',
    'domain_updated' => 'Dominio actualizado exitosamente',
    'domain_deleted' => 'Dominio eliminado exitosamente',
    'cannot_delete_domain_in_use' => 'No se puede eliminar un dominio que tiene subdominios activos',
    'at_least_one_domain' => 'Se requiere al menos un dominio activo',

    // ============================================
    // LISTA NEGRA
    // ============================================
    'blacklist_title' => 'Subdominios Bloqueados',
    'blacklist_description' => 'Los subdominios en esta lista no pueden ser usados por los usuarios',
    'add_to_blacklist' => 'Agregar a Lista Negra',
    'remove_from_blacklist' => 'Eliminar',
    'blacklist_word' => 'Palabra/Subdominio',
    'blacklist_word_placeholder' => 'Ingresa palabra a bloquear',
    'blacklist_reason' => 'Razón (opcional)',
    'blacklist_reason_placeholder' => '¿Por qué está bloqueado?',
    'import_blacklist' => 'Importar',
    'export_blacklist' => 'Exportar',
    'import_blacklist_help' => 'Sube un archivo de texto con una palabra por línea',
    'default_blacklist' => 'Cargar Lista Negra Predeterminada',
    'default_blacklist_confirm' => 'Esto agregará subdominios reservados comunes a la lista negra. ¿Continuar?',
    'clear_blacklist' => 'Limpiar Todo',
    'clear_blacklist_confirm' => '¿Estás seguro de querer eliminar todas las palabras bloqueadas?',
    'blacklist_empty' => 'La lista negra está vacía',
    'blacklist_count' => ':count palabras bloqueadas',

    // ============================================
    // REGISTROS DE ACTIVIDAD
    // ============================================
    'logs_title' => 'Registros de Actividad',
    'logs_description' => 'Rastrea todas las actividades relacionadas con subdominios',
    'log_action' => 'Acción',
    'log_user' => 'Usuario',
    'log_subdomain' => 'Subdominio',
    'log_details' => 'Detalles',
    'log_ip' => 'Dirección IP',
    'log_date' => 'Fecha',
    'log_action_create' => 'Creado',
    'log_action_update' => 'Actualizado',
    'log_action_delete' => 'Eliminado',
    'log_action_suspend' => 'Suspendido',
    'log_action_unsuspend' => 'Reactivado',
    'log_action_error' => 'Error',
    'clear_logs' => 'Limpiar Registros',
    'clear_logs_confirm' => '¿Estás seguro de querer limpiar todos los registros?',
    'logs_cleared' => 'Registros limpiados exitosamente',
    'no_logs' => 'No hay registros de actividad',
    'filter_by_action' => 'Filtrar por acción',
    'filter_by_user' => 'Filtrar por usuario',
    'filter_by_date' => 'Filtrar por fecha',
    'all_actions' => 'Todas las acciones',

    // ============================================
    // OPERACIONES MASIVAS
    // ============================================
    'bulk_operations' => 'Operaciones Masivas',
    'sync_dns' => 'Sincronizar Registros DNS',
    'sync_dns_description' => 'Sincroniza todos los subdominios con los registros DNS de Cloudflare',
    'sync_dns_confirm' => 'Esto verificará y actualizará todos los registros DNS. ¿Continuar?',
    'export_subdomains' => 'Exportar Subdominios',
    'export_subdomains_description' => 'Descargar un archivo CSV con todos los subdominios',
    'purge_orphaned' => 'Purgar Registros Huérfanos',
    'purge_orphaned_description' => 'Eliminar registros DNS que ya no tienen un servidor asociado',
    'purge_orphaned_confirm' => 'Esto eliminará registros DNS sin servidores asociados. ¿Continuar?',

    // ============================================
    // CONFIRMACIONES
    // ============================================
    'confirm_delete' => '¿Estás seguro de querer eliminar este subdominio?',
    'confirm_delete_text' => 'Esto eliminará los registros DNS. Los usuarios ya no podrán conectarse usando esta dirección. Esta acción no se puede deshacer.',
    'confirm_change' => '¿Estás seguro de querer cambiar tu subdominio?',
    'confirm_change_text' => 'Tu subdominio anterior dejará de funcionar inmediatamente. Los jugadores que usen la dirección antigua deberán usar la nueva.',
    'confirm_action' => 'Confirmar Acción',

    // ============================================
    // ENCABEZADOS DE TABLA
    // ============================================
    'table_subdomain' => 'Subdominio',
    'table_server' => 'Servidor',
    'table_user' => 'Usuario',
    'table_domain' => 'Dominio',
    'table_status' => 'Estado',
    'table_created' => 'Creado',
    'table_updated' => 'Actualizado',
    'table_actions' => 'Acciones',

    // ============================================
    // TOOLTIPS Y TEXTO DE AYUDA
    // ============================================
    'help_subdomain_format' => 'Usa solo letras minúsculas, números y guiones. No puede comenzar ni terminar con guion.',
    'help_dns_propagation' => 'Los cambios de DNS pueden tardar hasta 5 minutos en propagarse mundialmente.',
    'help_srv_record' => 'Los registros SRV permiten a los jugadores conectarse sin especificar el puerto.',
    'help_api_token_security' => 'Tu token de API se almacena encriptado y nunca se muestra después de guardarlo.',

    // ============================================
    // ESPECÍFICO DE MINECRAFT
    // ============================================
    'minecraft_connect' => 'Dirección del Servidor de Minecraft',
    'minecraft_java' => 'Java Edition',
    'minecraft_bedrock' => 'Bedrock Edition',
    'minecraft_port_note' => '¡Gracias a los registros SRV, los jugadores no necesitan ingresar el puerto!',

    // ============================================
    // FORMATOS DE TIEMPO
    // ============================================
    'time_just_now' => 'Justo ahora',
    'time_minutes_ago' => 'hace :minutes minutos',
    'time_hours_ago' => 'hace :hours horas',
    'time_days_ago' => 'hace :days días',
    'time_in_minutes' => 'en :minutes minutos',
    'time_in_hours' => 'en :hours horas',

    // ============================================
    // NAVEGACIÓN Y BREADCRUMBS
    // ============================================
    'nav_subdomains' => 'Subdominios',
    'nav_settings' => 'Configuración de Subdominios',
    'nav_blacklist' => 'Lista Negra de Subdominios',
    'nav_logs' => 'Registros de Subdominios',
    'breadcrumb_home' => 'Inicio',
    'breadcrumb_admin' => 'Administración',
    'breadcrumb_servers' => 'Servidores',
];
