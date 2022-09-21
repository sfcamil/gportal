<?php

namespace Drupal\gepsis\Form;

/**
 * User: Stoica Florin
 * Date: 3 mai 2021
 * Time: 15:26:26
 */
class FormArrays {

    public static function getAdherentInfoArray() {

        /*
         * fls = fieldset
         * lyt = layout
         */
        $acceptAccesAdhWebForm = array(
                'acc_acces_markup' => 'acc_acces_markup'
        );

        $adresseWebForm = [
                'adrss_main_fls' => 'adrss_main_adresse_fieldset ',
                'adrss_type_loc_lyt' => 'adrss_type_loc_code_layout',
                'adrss_type_adresse' => 'adrss_type_adresse',
                'adrss_country' => 'adrss_country',
                'adrss_city_et_code' => 'adrss_city_et_code',
                'adrss_compl_fls' => 'adrss_complement_adresse_fieldset',
                'adrss_layout_no_type_nom_adresse' => 'adrss_layout_no_type_nom_adresse',
                'adrss_no_adresse' => 'adrss_no_adresse',
                'adrss_type_voie' => 'adrss_type_voie',
                'adrss_nom_adresse' => 'adrss_nom_adresse',
                'adrss_layout_bat_esc_et_porte_adresse' => 'adrss_layout_bat_esc_et_porte_adresse',
                'adrss_batiment' => 'adrss_batiment',
                'adrss_escalier' => 'adrss_escalier',
                'adrss_etage' => 'adrss_etage',
                'adrss_porte' => 'adrss_porte',
                'adrss_complement_layout' => 'adrss_complement_layout',
                'adrss_complement_1' => 'adrss_complement_1',
                'adrss_complement_2' => 'adrss_complement_2',
                'adrss_message_fieldset' => 'adrss_message_fieldset',
                'addrs_markup_message' => 'addrs_markup_message',
                'adrss_votre_message' => 'adrss_votre_message',
                'adrss_adresse_oid_details' => 'adrss_adresse_oid_details',
                'adrss_fire_email_adresse' => 'adrss_fire_email_adresse',
                'adrss_zone_obligatoire' => 'adrss_zone_obligatoire',
                'adrss_adh_code' => 'adrss_adh_code'
        ];

        $bulletinAdhWebForm = array(
                'bul_adh_location' => 'bul_adh_location',
                'bul_adh_entete_adhesion' => 'bul_adh_entete_adhesion',
                'bul_adh_entete_adhesion_hors' => 'bul_adh_entete_adhesion_hors',
                'bul_adh_first_adhesion_fieldset' => 'bul_adh_first_adhesion_fieldset',
                'bul_adh_nom_prenom_layout' => 'bul_adh_nom_prenom_layout',
                'bul_adh_nom' => 'bul_adh_nom',
                'bul_adh_prenom' => 'bul_adh_prenom',
                'bul_adh_type_poste' => 'bul_adh_type_poste',
                'bul_adh_entreprise' => 'bul_adh_entreprise',
                'bul_adh_nom_prenom_erata' => 'bul_adh_nom_prenom_erata',
                'bul_adh_first_page_adhesion' => 'bul_adh_first_page_adhesion',
                'bul_adh_reglement_fieldset' => 'bul_adh_reglement_fieldset',
                'bul_adh_reglement_text' => 'bul_adh_reglement_text',
                'bul_adh_reglement_layout' => 'bul_adh_reglement_layout',
                'bul_adh_entreprise_droits' => 'bul_adh_entreprise_droits',
                'bul_adh_salaire' => 'bul_adh_salaire',
                'bul_adh_nombre_salaire' => 'bul_adh_nombre_salaire',
                'bul_adh_tva' => 'bul_adh_tva',
                'bul_adh_montant' => 'bul_adh_montant',
                'bul_adh_direction_text' => 'bul_adh_direction_text',
                'bul_adh_raison_sociale_fieldset' => 'bul_adh_raison_sociale_fieldset',
                'bul_adh_raison_sociale' => 'bul_adh_raison_sociale',
                'bul_adh_enseigne_commerciale' => 'bul_adh_enseigne_commerciale',
                'bul_adh_activite_entreprise' => 'bul_adh_activite_entreprise',
                'bul_adh_naf_siret_layout' => 'bul_adh_naf_siret_layout',
                'bul_adh_code_naf' => 'bul_adh_code_naf',
                'bul_adh_numero_siret' => 'bul_adh_numero_siret',
                'bul_adh_nombre_salaries' => 'bul_adh_nombre_salaries',
                'bul_adh_forme_juridique' => 'bul_adh_forme_juridique',
                'bul_adh_adresse_etablissement_fieldset' => 'bul_adh_adresse_etablissement_fieldset',
                'bul_adh_rue_etab_princ' => 'bul_adh_rue_etab_princ',
                'bul_adh_code_ville_etab_princ_layout' => 'bul_adh_code_ville_etab_princ_layout',
                'bul_adh_ville_etab_princ' => 'bul_adh_ville_etab_princ',
                'bul_adh_code_postal_etab_princ' => 'bul_adh_code_postal_etab_princ',
                'bul_adh_phone_copie_etab_princ_layout' => 'bul_adh_phone_copie_etab_princ_layout',
                'bul_adh_telephone_etab_princ' => 'bul_adh_telephone_etab_princ',
                'bul_adh_telecopie_etab_princ' => 'bul_adh_telecopie_etab_princ',
                'bul_adh_mail_gsm_etab_princ_layout' => 'bul_adh_mail_gsm_etab_princ_layout',
                'bul_adh_mail_etab_princ' => 'bul_adh_mail_etab_princ',
                'bul_adh_portable_etab_princ' => 'bul_adh_portable_etab_princ',
                'bul_adh_adresse_siege_fieldset' => 'bul_adh_adresse_siege_fieldset',
                'bul_adh_rue_siege' => 'bul_adh_rue_siege',
                'bul_adh_code_ville_siege_layout' => 'bul_adh_code_ville_siege_layout',
                'bul_adh_ville_siege' => 'bul_adh_ville_siege',
                'bul_adh_code_postal_siege' => 'bul_adh_code_postal_siege',
                'bul_adh_phone_copie_siege_layout' => 'bul_adh_phone_copie_siege_layout',
                'bul_adh_telephone_siege' => 'bul_adh_telephone_siege',
                'bul_adh_telecopie_siege' => 'bul_adh_telecopie_siege',
                'bul_adh_mail_gsm_siege_layout' => 'bul_adh_mail_gsm_siege_layout',
                'bul_adh_mail_siege' => 'bul_adh_mail_siege',
                'bul_adh_portable_siege' => 'bul_adh_portable_siege',
                'bul_adh_interlocuteur_fieldset' => 'bul_adh_interlocuteur_fieldset',
                'bul_adh_contact_interlocuteur' => 'bul_adh_contact_interlocuteur',
                'bul_adh_function_telephone_layout' => 'bul_adh_function_telephone_layout',
                'bul_adh_fonction_service' => 'bul_adh_fonction_service',
                'bul_adh_telephone' => 'bul_adh_telephone',
                'bul_adh_adresse_facturation_fieldset' => 'bul_adh_adresse_facturation_fieldset',
                'bul_adh_rue_fact' => 'bul_adh_rue_fact',
                'bul_adh_code_ville_fact_layout' => 'bul_adh_code_ville_fact_layout',
                'bul_adh_ville_fact' => 'bul_adh_ville_fact',
                'bul_adh_code_postal_fact' => 'bul_adh_code_postal_fact',
                'bul_adh_phone_copie_fact_layout' => 'bul_adh_phone_copie_fact_layout',
                'bul_adh_telephone_fact' => 'bul_adh_telephone_fact',
                'bul_adh_telecopie_fact' => 'bul_adh_telecopie_fact',
                'bul_adh_mail_gsm_fact_layout' => 'bul_adh_mail_gsm_fact_layout',
                'bul_adh_mail_fact' => 'bul_adh_mail_fact',
                'bul_adh_portable_fact' => 'bul_adh_portable_fact',
                'bul_adh_coordonnees_medecin_fieldset' => 'bul_adh_coordonnees_medecin_fieldset',
                'bul_adh_nom_medecin' => 'bul_adh_nom_medecin',
                'bul_adh_nom_adresse_tel_service' => 'bul_adh_nom_adresse_tel_service',
                'bul_adh_etab_secondaire_fieldset' => 'bul_adh_etab_secondaire_fieldset',
                'bul_adh_etab_secondaire_1_fieldset' => 'bul_adh_etab_secondaire_1_fieldset',
                'bul_adh_rue_etab_secondaire_1' => 'bul_adh_rue_etab_secondaire_1',
                'bul_adh_code_ville_etab_secondaire_1_layout' => 'bul_adh_code_ville_etab_secondaire_1_layout',
                'bul_adh_ville_etab_secondaire_1' => 'bul_adh_ville_etab_secondaire_1',
                'bul_adh_code_postal_etab_secondaire_1' => 'bul_adh_code_postal_etab_secondaire_1',
                'bul_adh_phone_copie_etab_secondaire_1_layout' => 'bul_adh_phone_copie_etab_secondaire_1_layout',
                'bul_adh_telephone_etab_secondaire_1' => 'bul_adh_telephone_etab_secondaire_1',
                'bul_adh_telecopie_etab_secondaire_1' => 'bul_adh_telecopie_etab_secondaire_1',
                'bul_adh_etab_secondaire_2_fieldset' => 'bul_adh_etab_secondaire_2_fieldset',
                'bul_adh_rue_etab_secondaire_2' => 'bul_adh_rue_etab_secondaire_2',
                'bul_adh_code_ville_etab_secondaire_2_layout' => 'bul_adh_code_ville_etab_secondaire_2_layout',
                'bul_adh_ville_etab_secondaire_2' => 'bul_adh_ville_etab_secondaire_2',
                'bul_adh_code_postal_etab_secondaire_2' => 'bul_adh_code_postal_etab_secondaire_2',
                'bul_adh_phone_copie_etab_secondaire_2_layout' => 'bul_adh_phone_copie_etab_secondaire_2_layout',
                'bul_adh_telephone_etab_secondaire_2' => 'bul_adh_telephone_etab_secondaire_2',
                'bul_adh_telecopie_etab_secondaire_2' => 'bul_adh_telecopie_etab_secondaire_2',
                'bul_adh_compte_rattacher_fieldset' => 'bul_adh_compte_rattacher_fieldset',
                'bul_adh_compte_rattacher_portefeuille' => 'bul_adh_compte_rattacher_portefeuille',
                'bul_adh_nom_adresse_compte' => 'bul_adh_nom_adresse_compte',
                'bul_adh_ne_pas_oublier_text' => 'bul_adh_ne_pas_oublier_text',
                'bul_adh_pieces_joindre_text' => 'bul_adh_pieces_joindre_text',
                'bul_adh_location_hidden' => 'bul_adh_location_hidden',
                'bul_adh_forme_juridique_societe_hidden' => 'bul_adh_forme_juridique_societe_hidden',
                'bul_adh_forme_juridique_entreprise_hidden' => 'bul_adh_forme_juridique_entreprise_hidden',
                'bul_adh_compte_rattacher_hidden' => 'bul_adh_compte_rattacher_hidden',
                'bul_adh_adhesion_effet_text' => 'bul_adh_adhesion_effet_text'
        );

        $cabinetAccesWebForm = array(
                'cab_acces_message_fieldset' => 'cab_acces_message_fieldset',
                'cab_acces_markup_message' => 'cab_acces_markup_message',
                'cab_acces_user_table' => 'cab_acces_user_table',
                'cab_acces_texte_avant_bouton' => 'cab_acces_texte_avant_bouton',
                'cab_acces_hidden_code_adherent' => 'cab_acces_hidden_code_adherent',
                'cab_acces_hidden_email' => 'cab_acces_hidden_email',
                'cab_acces_hidden_email_logged' => 'cab_acces_hidden_email_logged',
                'cab_acces_hidden_nom_adherent' => 'cab_acces_hidden_nom_adherent',
                'cab_acces_hidden_nom_cabinet' => 'cab_acces_hidden_nom_cabinet',
                'cab_acces_hidden_user_cabinet' => 'cab_acces_hidden_user_cabinet',
                'cab_acces_hidden_adh_code' => 'cab_acces_hidden_adh_code'
        );

        $changementSituationWebForm = array(
                'change_sit_titre1' => 'change_sit_titre1',
                'change_sit_date_declararation_employer_personnel' => 'change_sit_date_declararation_employer_personnel',
                'change_sit_date_declararation_redressement_judiciaire' => 'change_sit_date_declararation_redressement_judiciaire',
                'change_sit_date_declararation_liquidation_judiciaire' => 'change_sit_date_declararation_liquidation_judiciaire',
                'change_sit_date_declararation_ceder_entreprise' => 'change_sit_date_declararation_ceder_entreprise',
                'change_sit_information_succcesseur' => 'change_sit_information_succcesseur',
                'change_sit_message_service' => 'change_sit_message_service'
        );

        $contactWebForm = array(
                'contact_general_delete' => 'contact_general_delete',
                'contact_details_fieldset' => 'contact_details_fieldset',
                'contact_type' => 'contact_type',
                'contact_title_types_layout' => 'contact_title_types_layout',
                'contact_title' => 'contact_title',
                'contact_fonction' => 'contact_fonction',
                'contact_person_layout' => 'contact_person_layout',
                'contact_last_name' => 'contact_last_name',
                'contact_first_name' => 'contact_first_name',
                'contact_email_layout' => 'contact_email_layout',
                'contact_email' => 'contact_email',
                'contact_telephone_portable' => 'contact_telephone_portable',
                'contact_type_adresse_layout' => 'contact_type_adresse_layout',
                'contact_adresse' => 'contact_adresse',
                'contact_creer_une_adresse_fieldset' => 'contact_creer_une_adresse_fieldset',
                'contact_city_code_layout' => 'contact_city_code_layout',
                'contact_type_adresse' => 'contact_type_adresse',
                'contact_city_et_code' => 'contact_city_et_code',
                'contact_rue_layout' => 'contact_rue_layout',
                'contact_complement_adresse' => 'contact_complement_adresse',
                'contact_libelle_voie' => 'contact_libelle_voie',
                'contact_bp_lieu_dit' => 'contact_bp_lieu_dit',
                'contact_zone_obligatoire' => 'contact_zone_obligatoire',
                'contact_adherent_oid_details' => 'contact_adherent_oid_details',
                'contact_fire_email' => 'contact_fire_email',
                'contact_adh_code' => 'contact_adh_code'
        );

        $contactAssistanteWebForm = array(
                'cont_assis_adherent' => 'cont_assis_adherent',
                'cont_assis_fieldset' => 'cont_assis_fieldset',
                'cont_assis_email' => 'cont_assis_email',
                'cont_assis_subject' => 'cont_assis_subject',
                'cont_assis_message' => 'cont_assis_message',
                'cont_assis_fire_email' => 'cont_assis_fire_email'
        );

        $controleListeSalariesWebForm = array(
                'cont_sal_text_adhesion_effet' => 'cont_sal_text_adhesion_effet'
        );

        $demandeRdvWebForm = array(
                'rdv_adherent' => 'rdv_adherent',
                'rdv_demande_visite_reprise' => 'rdv_demande_visite_reprise',
                'rdv_email_reprise' => 'rdv_email_reprise',
                'rdv_subject_reprise' => 'rdv_subject_reprise',
                'rdv_motif' => 'rdv_motif',
                'rdv_comment' => 'rdv_comment',
                'rdv_dates_reprise_layout' => 'rdv_dates_reprise_layout',
                'rdv_date_debut_arret' => 'rdv_date_debut_arret',
                'rdv_date_reprise' => 'rdv_date_reprise',
                'rdv_motif_arret' => 'rdv_motif_arret',
                'rdv_message_reprise' => 'rdv_message_reprise',
                'rdv_fire_email_adresse' => 'rdv_fire_email_adresse',
                'rdv_zone_obligatoire' => 'rdv_zone_obligatoire',
                'rdv_adh_code' => 'rdv_adh_code'
        );

        $dueFlagWebForm = array(
                'due_flag_fieldset' => 'due_flag_fieldset',
                'due_flag_traitament_automatique_ursaf' => 'due_flag_traitament_automatique_ursaf',
                'due_flag_description' => 'due_flag_description'
        );

        $dueAutoWebForm = array(
                'due_auto_all_fieldset' => 'due_auto_all_fieldset',
                'due_auto_traiter_due_activees' => 'due_auto_traiter_due_activees',
                'due_auto_select_fieldset' => 'due_auto_select_fieldset',
                'due_auto_select_cabinet_comptable' => 'due_auto_select_cabinet_comptable',
                'due_auto_select_adherent' => 'due_auto_select_adherent',
                'due_auto_all_dues' => 'due_auto_all_dues',
                'due_auto_traiter_tout_adherent' => 'due_auto_traiter_tout_adherent'
        );

        $declarationAnuelleWebForm = array(
                'dec_an_titre1' => 'dec_an_titre1',
                'dec_an_buttons_fieldset' => 'dec_an_buttons_fieldset',
                'dec_an_buttons_markup' => 'dec_an_buttons_markup',
                'dec_an_titre2' => 'dec_an_titre2',
                'dec_an_effectif_global' => 'dec_an_effectif_global',
                'dec_an_effectif_total' => 'dec_an_effectif_total',
                'dec_an_titre3' => 'dec_an_titre3',
                'dec_an_montant_annuel' => 'dec_an_montant_annuel',
                'dec_an_liste_adherents_concernees' => 'dec_an_liste_adherents_concernees',
                'dec_an_url_decla_ostra' => 'dec_an_url_decla_ostra',
                'dec_an_show_page_2' => 'dec_an_show_page_2',
                'dec_an_adh_code' => 'dec_an_adh_code'
        );

        $effacerAccesUserWebForm = array(
                'eff_acces_layout' => 'eff_acces_layout',
                'eff_acces_select_adherent' => 'eff_acces_select_adherent',
                'eff_acces_utilisateur' => 'eff_acces_utilisateur'
        );

        $ficheAdhWebForm = array(
                'adherent' => 'adherent',
                'flexboxentreprise_layout' => 'flexboxentreprise_layout',
                'adh_code_entreprise' => 'adh_code_entreprise',
                'adh_nom_entreprise' => 'adh_nom_entreprise',
                'adh_date_d_inscription_de_l_adherent' => 'adh_date_d_inscription_de_l_adherent',
                'adh_description_entreprise' => 'adh_description_entreprise',
                'flexbox_entreprise_siren_siret_layout' => 'flexbox_entreprise_siren_siret_layout',
                'adh_siren_entreprise' => 'adh_siren_entreprise',
                'adh_siret_entreprise' => 'adh_siret_entreprise',
                'flexbox_entreprise_naf_layout' => 'flexbox_entreprise_naf_layout',
                'adh_full_naf_entreprise' => 'adh_full_naf_entreprise',
                'adh_message_fieldset' => 'adh_message_fieldset',
                'adh_markup_message' => 'adh_markup_message',
                'adh_votre_message_atext' => 'adh_votre_message_atext',
                'adh_entreprise_oid_details' => 'adh_entreprise_oid_details',
                'adh_fire_email_entreprise' => 'adh_fire_email_entreprise',
                'adh_code_adherent' => 'adh_code_adherent',
                'actions' => 'actions'
        );

        $ficheSalWebForm = array(
                'sal_person_details_fieldset' => 'sal_person_details_fieldset',
                'trav_details_planification_active' => 'trav_details_planification_active',
                'sal_title_sex_layout' => 'sal_title_sex_layout',
                'sal_title' => 'sal_title',
                'sal_sexe' => 'sal_sexe',
                'sal_birth_date' => 'sal_birth_date',
                'sal_numero_securite_social' => 'sal_numero_securite_social',
                'sal_numero_tel_portable' => 'sal_numero_tel_portable',
                'sal_name_layout' => 'sal_name_layout',
                'sal_nom_usage' => 'sal_nom_usage',
                'sal_first_name' => 'sal_first_name',
                'sal_marital_name' => 'sal_marital_name',
                'sal_adresse_personnelle' => 'sal_adresse_personnelle',
                'sal_telephone_email_layout' => 'sal_telephone_email_layout',
                'sal_telephone_fixe' => 'sal_telephone_fixe',
                'sal_telephone_portable' => 'sal_telephone_portable',
                'sal_email' => 'sal_email',
                'sal_numero_et_rue_layout' => 'sal_numero_et_rue_layout',
                'sal_no_adresse' => 'sal_no_adresse',
                'sal_type_voie' => 'sal_type_voie',
                'sal_nom_adresse' => 'sal_nom_adresse',
                'sal_layout_bat_esc_et_porte_adresse' => 'sal_layout_bat_esc_et_porte_adresse',
                'sal_batiment' => 'sal_batiment',
                'sal_escalier' => 'sal_escalier',
                'sal_etage' => 'sal_etage',
                'sal_porte' => 'sal_porte',
                'sal_complement_layout' => 'sal_complement_layout',
                'sal_complement_1' => 'sal_complement_1',
                'sal_complement_2' => 'sal_complement_2',
                'sal_localite_complement_layout' => 'sal_localite_complement_layout',
                'sal_country' => 'sal_country',
                'sal_city_et_code' => 'sal_city_et_code',
                'sal_travailleur_details' => 'sal_travailleur_details',
                'sal_employement_layout' => 'sal_employement_layout',
                'sal_employement_type' => 'sal_employement_type',
                'sal_examen_type' => 'sal_examen_type',
                'sal_contrat_dates' => 'sal_contrat_dates',
                'sal_trav_start_date' => 'sal_trav_start_date',
                'sal_trav_end_date' => 'sal_trav_end_date',
                'sal_start_date_hidden' => 'sal_start_date_hidden',
                'sal_end_date_hidden' => 'sal_end_date_hidden',
                'sal_last_visite_date_hidden' => 'sal_last_visite_date_hidden',
                'sal_trav_oid_details' => 'sal_trav_oid_details',
                'sal_fire_email_person' => 'sal_fire_email_person',
                'sal_adh_code' => 'sal_adh_code'
        );

        $listeAdhWebForm = array(
                'adh_value_show_form' => 'adh_value_show_form'
        );

        $modifContratWebForm = array(
                'contrat_message_fieldset' => 'contrat_message_fieldset',
                'contrat_message_risques' => 'contrat_message_risques',
                'contrat_details_fieldset' => 'contrat_details_fieldset',
                'contrat_fonction_occupe' => 'contrat_fonction_occupe',
                'contrat_employement_risques_layout' => 'contrat_employement_risques_layout',
                'contrat_type' => 'contrat_type',
                'contrat_dates_risques_layout' => 'contrat_dates_risques_layout',
                'contrat_date_debut' => 'contrat_date_debut',
                'contrat_date_debut_hidden' => 'contrat_date_debut_hidden',
                'contrat_date_fin' => 'contrat_date_fin',
                'contrat_date_fin_hidden' => 'contrat_date_fin_hidden',
                'contrat_risques_fieldset' => 'contrat_risques_fieldset',
                'contrat_aide_fieldset' => 'contrat_aide_fieldset',
                'contrat_lien_aide_sir' => 'contrat_lien_aide_sir',
                'contrat_risques' => 'contrat_risques',
                'contrat_categorie_declaree' => 'contrat_categorie_declaree',
                'contrat_periodicite_indicative' => 'contrat_periodicite_indicative',
                'contrat_examen_type_oid' => 'contrat_examen_type_oid',
                'contrat_fire_email' => 'contrat_fire_email',
                'contrat_adh_code' => 'contrat_adh_code',
                'contrat_trav' => 'contrat_trav',
                'contrat_rendez_vous' => 'contrat_rendez_vous',
                'contrat_persnomprenom' => 'contrat_persnomprenom'
        );

        $newSalarieWebForm = array(
                'new_sal_person_details_wp' => 'new_sal_person_details_wp',
                'new_sal_atention_navigateur' => 'new_sal_atention_navigateur',
                'new_sal_person_details_fieldset' => 'new_sal_person_details_fieldset',
                'new_sal_title_sex_name_layout' => 'new_sal_title_sex_name_layout',
                'new_sal_title' => 'new_sal_title',
                'new_sal_sexe' => 'new_sal_sexe',
                'new_sal_nom_usage' => 'new_sal_nom_usage',
                'new_sal_first_name' => 'new_sal_first_name',
                'new_sal_nom_naissance' => 'new_sal_nom_naissance',
                'new_sal_date_ss_gsm_layout' => 'new_sal_date_ss_gsm_layout',
                'new_sal_birth_date' => 'new_sal_birth_date',
                'new_sal_numero_securite_social' => 'new_sal_numero_securite_social',
                'new_sal_numero_tel_portable' => 'new_sal_numero_tel_portable',
                'new_sal_zone_obligatoire' => 'new_sal_zone_obligatoire',
                'new_sal_break_create_trav_wp' => 'new_sal_break_create_trav_wp',
                'new_sal_contrat_travail_fieldset' => 'new_sal_contrat_travail_fieldset',
                'new_sal_function_trav' => 'new_sal_function_trav',
                'new_sal_employement_type' => 'new_sal_employement_type',
                'new_sal_start_date' => 'new_sal_start_date',
                'new_sal_end_date' => 'new_sal_end_date',
                'new_sal_salarie_supprime' => 'new_sal_salarie_supprime',
                'new_sal_zone_obligatoire_2' => 'new_sal_zone_obligatoire_2',
                'new_sal_break_trav_details_wp' => 'new_sal_break_trav_details_wp',
                'new_sal_numero_et_rue_layout' => 'new_sal_numero_et_rue_layout',
                'new_sal_no_adresse' => 'new_sal_no_adresse',
                'new_sal_type_voie' => 'new_sal_type_voie',
                'new_sal_nom_adresse' => 'new_sal_nom_adresse',
                'new_sal_bat_esc_et_porte_adresse_layout' => 'new_sal_bat_esc_et_porte_adresse_layout',
                'new_sal_batiment' => 'new_sal_batiment',
                'new_sal_escalier' => 'new_sal_escalier',
                'new_sal_etage' => 'new_sal_etage',
                'new_sal_porte' => 'new_sal_porte',
                'new_sal_localite_complement_layout' => 'new_sal_localite_complement_layout',
                'new_sal_country' => 'new_sal_country',
                'new_sal_city_et_code' => 'new_sal_city_et_code',
                'new_sal_complement_layout' => 'new_sal_complement_layout',
                'new_sal_complement_1' => 'new_sal_complement_1',
                'new_sal_complement_2' => 'new_sal_complement_2',
                'new_sal_break_trav_poste_wp' => 'new_sal_break_trav_poste_wp',
                'new_sal_poste_detail_fieldset_1' => 'new_sal_poste_detail_fieldset_1',
                'new_sal_poste_start_date_1' => 'new_sal_poste_start_date_1',
                'new_sal_poste_markup_1' => 'new_sal_poste_markup_1',
                'new_sal_poste_codes_pcs_1' => 'new_sal_poste_codes_pcs_1',
                'new_sal_poste_new_code_1' => 'new_sal_poste_new_code_1',
                'new_sal_poste_comment_1' => 'new_sal_poste_comment_1',
                'new_sal_rebuild_by_ajax_1' => 'new_sal_rebuild_by_ajax_1',
                'new_sal_poste_status_1' => 'new_sal_poste_status_1',
                'new_sal_poste_detail_fieldset_2' => 'new_sal_poste_detail_fieldset_2',
                'new_sal_poste_start_date_2' => 'new_sal_poste_start_date_2',
                'new_sal_poste_codes_pcs_2' => 'new_sal_poste_codes_pcs_2',
                'new_sal_poste_new_code_2' => 'new_sal_poste_new_code_2',
                'new_sal_poste_comment_2' => 'new_sal_poste_comment_2',
                'new_sal_rebuild_by_ajax_2' => 'new_sal_rebuild_by_ajax_2',
                'new_sal_poste_status_2' => 'new_sal_poste_status_2',
                'new_sal_poste_detail_fieldset_3' => 'new_sal_poste_detail_fieldset_3',
                'new_sal_poste_start_date_3' => 'new_sal_poste_start_date_3',
                'new_sal_poste_codes_pcs_3' => 'new_sal_poste_codes_pcs_3',
                'new_sal_poste_new_code_3' => 'new_sal_poste_new_code_3',
                'new_sal_poste_comment_3' => 'new_sal_poste_comment_3',
                'new_sal_rebuild_by_ajax_3' => 'new_sal_rebuild_by_ajax_3',
                'new_sal_poste_status_3' => 'new_sal_poste_status_3',
                'new_sal_break_trav_risques_wp' => 'new_sal_break_trav_risques_wp',
                'new_sal_risques_fieldset' => 'new_sal_risques_fieldset',
                'new_sal_aide_fieldset' => 'new_sal_aide_fieldset',
                'new_sal_lien_aide_sir' => 'new_sal_lien_aide_sir',
                'new_sal_risques' => 'new_sal_risques',
                'new_sal_categorie_declaree' => 'new_sal_categorie_declaree',
                'new_sal_periodicite_indicative' => 'new_sal_periodicite_indicative',
                'new_sal_examen_type_oid' => 'new_sal_examen_type_oid',
                'new_sal_fire_email' => 'new_sal_fire_email',
                'new_sal_adh_code' => 'new_sal_adh_code'
        );

        $newAdherentWebForm = array(
                'new_compte_codes_adherents' => 'new_compte_codes_adherents',
                'new_compte_explications' => 'new_compte_explications'
        );

        $posteWebForm = array(
                'poste_detail_fieldset' => 'poste_detail_fieldset',
                'poste_salarie' => 'poste_salarie',
                'poste_start_date' => 'poste_start_date',
                'poste_start_date_hidden' => 'poste_start_date_hidden',
                'poste_markup' => 'poste_markup',
                'poste_codes_pcs' => 'poste_codes_pcs',
                'poste_new_code' => 'poste_new_code',
                'poste_comment' => 'poste_comment',
                'poste_status' => 'poste_status',
                'poste_desactivation' => 'poste_desactivation',
                'poste_desactivation_markup' => 'poste_desactivation_markup',
                'poste_date_desactivation' => 'poste_date_desactivation',
                'poste_fire_email' => 'poste_fire_email',
                'poste_zone_obligatoire' => 'poste_zone_obligatoire',
                'poste_adh_code' => 'poste_adh_code'
        );

        $rdvWebForm = array(
                'rdv_modif_adherent' => 'rdv_modif_adherent',
                'rdv_modif_fieldset' => 'rdv_modif_fieldset',
                'rdv_modif_email' => 'rdv_modif_email',
                'rdv_modif_subject' => 'rdv_modif_subject',
                'rdv_modif_message' => 'rdv_modif_message',
                'rdv_modif_fire_email' => 'rdv_modif_fire_email'
        );

        return ;
    }
}
