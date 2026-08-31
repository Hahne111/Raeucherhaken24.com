<?php
/* =====================================================================
   ORGABOARD · ZENTRALE RECHTEPRÜFUNG FÜR DIE API   (api-guard.php)
   ---------------------------------------------------------------------
   Bisher haben mehrere Schreibaktionen in api.php ausschliesslich eine
   Anmeldung vorausgesetzt. Die Bedienelemente waren im Frontend zwar
   ausgeblendet, der Endpunkt selbst liess sich aber von jedem
   angemeldeten Konto direkt aufrufen. Betroffen waren unter anderem
   Produktpreise, Lagerbestände, Bestellstatus und Systemeinstellungen.

   Diese Datei prüft die Berechtigung zentral, bevor eine Aktion
   ausgeführt wird. Die vorhandenen Prüfungen in den einzelnen
   Handlern bleiben unverändert bestehen – sie greifen zusätzlich.

   Grundsatz: Es wird jeweils das Recht verlangt, das der vorhandene
   Rechtekatalog für diesen Vorgang bereits vorsieht. Es werden keine
   neuen Rechte erfunden und keine bestehenden entfernt.
   ===================================================================== */
declare(strict_types=1);

/**
 * Aktion → benötigtes Recht.
 * '@admin' bedeutet: nur Administratoren.
 * Aktionen, die ausschliesslich eigene Daten betreffen, sind bewusst
 * nur mit dem passenden Ansichtsrecht belegt; die Einschränkung auf den
 * eigenen Datensatz erfolgt weiterhin im Handler selbst.
 */
function rh24_api_permission_map(): array {
    return [
        // ---------------------------------------------------- Termine
        'appointment_get'              => 'view_appointments',
        'appointment_customer_search'  => 'view_appointments',
        'appointment_save'             => 'edit_appointments',
        'appointment_status'           => 'edit_appointments',
        'appointment_delete'           => 'edit_appointments',
        'appointment_task_toggle'      => 'edit_appointments',
        'appointment_move'             => 'edit_appointments',
        'appointment_duplicate'        => 'edit_appointments',
        'appointment_template_save'    => 'edit_appointments',
        'appointment_template_delete'  => 'edit_appointments',
        'appointment_email_invite'     => 'edit_appointments',
        'appointment_reminders_run'    => '@admin',

        // ----------------------------------------------- Fahrtenbuch
        'triplog_get'          => 'view_triplog',
        'trip_vehicle_save'    => 'edit_triplog',
        'trip_save'            => 'edit_triplog',
        'trip_finalize'        => 'edit_triplog',
        'trip_correct'         => 'edit_triplog',
        'trip_delete'          => 'edit_triplog',
        'trip_route_optimize'  => 'view_triplog',
        'vehicle_key_lookup'   => 'edit_triplog',

        // -------------------------------------------------- Vertrieb
        'leaderboard_get'         => 'view_leaderboard',
        'dealer_calendar_get'     => 'view_dealer_visits',
        'dealer_visit_complete'   => 'manage_dealer_visits',
        'dealer_visit_reschedule' => 'manage_dealer_visits',
        'consultation_save'       => 'save_consultations',
        'manual_order_create'     => 'create_orders',

        // ------------------------------------------------ Gebietsbuch
        'territory_directory_search'  => 'view_territory_book',
        'territory_directory_list'    => 'view_territory_book',
        'territory_directory_history' => 'view_territory_book',
        'territory_directory_contact' => 'contact_territory_book',

        // ----------------------------------------------------- Kunden
        'customer_save'      => 'edit_customers',
        'customer_verify'    => 'edit_customers',
        'customer_note'      => 'edit_customers',
        'address_localities' => 'view_customers',
        'address_streets'    => 'view_customers',

        // --------------------------------------------------- Aufträge
        'order_update'     => 'edit_orders',
        'prototype_update' => 'edit_prototypes',

        // -------------------------------------------------- Produktion
        'production_update' => 'edit_production',

        // ------------------------------------------------------- Lager
        'inventory_update'     => 'edit_inventory',
        'warehouse_save'       => 'edit_inventory',
        'warehouse_stock_book' => 'edit_inventory',

        // ---------------------------------------------------- Produkte
        'product_save'           => 'edit_products',
        'product_quick_update'   => 'edit_products',
        'product_publish_repair' => 'edit_products',
        'product_publish_probe'  => 'view_products',
        'product_delete'         => '@admin',

        // ----------------------------------------------------- Versand
        'shipping_label_get'  => 'view_shipping',
        'shipping_label_save' => 'view_shipping',

        // -------------------------------------------------- Dokumente
        'document_get'     => 'view_documents',
        'document_history' => 'view_documents',

        // --------------------------------- Nur für Administratoren
        'dealer_save'   => '@admin',
        'review_update' => '@admin',
        'content_save'  => '@admin',
        'settings_save' => '@admin',

        // ------------------------------------------------- Eigenes Konto
        'password_change' => 'change_own_password',
        'message_read'    => 'view_messages',
        'message_send'    => 'send_messages',
    ];
}

/**
 * Prüft die Berechtigung für eine Aktion. Nicht aufgeführte Aktionen
 * behalten ihr bisheriges Verhalten (Anmeldung plus die im Handler
 * vorhandene Prüfung) – so wird kein bestehender Ablauf blockiert.
 */
function rh24_api_guard(string $action): void {
    $map = rh24_api_permission_map();
    if (!isset($map[$action])) {
        return;
    }
    $needed = $map[$action];
    if ($needed === '@admin') {
        rh24_require_admin();
        return;
    }
    rh24_require_permission($needed);
}
