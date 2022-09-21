<?php

namespace Drupal\gepsis\Utility;

use Drupal\gepsis\Controller\GepsisOdataReadClass;

/**
 * User: Stoica Florin
 * Date: 4 mai 2021
 * Time: 11:53:40
 */
class GetAllFunctions
{

    public static function getAllAdherentsByOid() {
        if (!isset($_SESSION['finalListeAllAdherentsByOid'])) {
            $viewListeAllAdherentsByOid = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO');
            if (empty($viewListeAllAdherentsByOid)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllAdherentsByOid as $value) {
                $finalListeAllAdherentsByOid[$value->ENTR_O_ID] = $value;
            }
            $_SESSION['finalListeAllAdherentsByOid'] = $finalListeAllAdherentsByOid;
        }
        return $_SESSION['finalListeAllAdherentsByOid'];
    }


    public static function getAllAdherentsByName() {
        // unset($_SESSION['finalListeAllCityes']);
        if (!isset($_SESSION['finalListeAllAdherentsByName'])) {
            $viewListeAllAdherentsByName = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO');
            if (empty($viewListeAllAdherentsByName)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllAdherentsByName as $value) {
                $finalListeAllAdherentsByName[trim($value->NOM)] = $value;
            }
            $_SESSION['finalListeAllAdherentsByName'] = $finalListeAllAdherentsByName;
        }
        return $_SESSION['finalListeAllAdherentsByName'];
    }

    public static function getAllAdherentsByCode() {
        // unset($_SESSION['finalListeAllAdherentsByCode']);
        if (!isset($_SESSION['finalListeAllAdherentsByCode'])) {
            $viewListeAllAdherentsByCode = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO');
            if (empty($viewListeAllAdherentsByCode)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllAdherentsByCode as $value) {
                $finalListeAllAdherentsByCode[trim($value->ENTR_CODE)] = $value;
            }
            $_SESSION['finalListeAllAdherentsByCode'] = $finalListeAllAdherentsByCode;
        }
        return $_SESSION['finalListeAllAdherentsByCode'];
    }

    public static function getCityByOid($city) {
        $viewCity = GepsisOdataReadClass::getOdataClassValues('V1_ALL_CITYES', 'CITY_O_ID eq ' . $city);
        if (empty($viewCity)) {
            \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
            return;
        }
        $finalCity[$viewCity[0]->CITY_O_ID] = $viewCity[0]->CITY_LABEL . ' - ' . $viewCity[0]->CITY_CODE;
        return $finalCity;
    }

    public static function getAllCityes() {
        // unset($_SESSION['finalListeAllCityes']);
        if (!isset($_SESSION['finalListeAllCityes'])) {
            $viewListeAllCityes = GepsisOdataReadClass::getOdataClassValues('V1_ALL_CITYES');
            if (empty($viewListeAllCityes)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllCityes as $value) {
                $finalListeAllCityes[$value->CITY_O_ID] = $value->CITY_LABEL . ' - ' . $value->CITY_CODE;
            }
            $_SESSION['finalListeAllCityes'] = $finalListeAllCityes;
        }
        return $_SESSION['finalListeAllCityes'];
    }

    public static function getAllEmplTypeCat() {
        if (!isset($_SESSION['finalListeAllEmplTypeCat'])) {
            $customer = GepsisOdataReadClass::getOdataClassValues('V1_EMP_TYP_CAT');
            if (empty($customer)) {
                vsm(t('No result returned. Please check your query and the endpoint status (getAllEmplTypeCat).'));
                return;
            }
            $finalListeAllEmplTypeCatArray = array();
            foreach ($customer as $value) {
                $finalListeAllEmplTypeCatArray[$value->EMP_TYP_CAT_O_ID] = $value->EMP_TYP_CAT_LABEL;
            }
            $_SESSION['finalListeAllEmplTypeCat'] = $finalListeAllEmplTypeCatArray;
        }
        return $_SESSION['finalListeAllEmplTypeCat'];
    }

    public static function getAllDecretCriteria() {
        // unset($_SESSION['finalListeAllDecretCriteria']);
        if (!isset($_SESSION['finalListeAllDecretCriteria'])) {
            $customer = GepsisOdataReadClass::getOdataClassValues('V1_ALL_DECRET_CRITERIA');
            if (empty($customer)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            // $customer = record_sort(object_to_array($customer), 'DECRET_CRITERIA_ORDER_INT');
            foreach ($customer as $value) {
                // $finalListeAllDecretCriteria[$value['DECRET_CRITERIA_O_ID']] = $value['DECRET_CRITERIA_LABEL'];
                $finalListeAllDecretCriteria[$value->DECRET_CRITERIA_O_ID] = $value->DECRET_CRITERIA_LABEL;
            }
            $_SESSION['finalListeAllDecretCriteria'] = $finalListeAllDecretCriteria;
        }
        return $_SESSION['finalListeAllDecretCriteria'];
    }

    public static function getAllCountries() {
        if (!isset($_SESSION['finalListeAllCountries'])) {
            $viewListeAllCountries = GepsisOdataReadClass::getOdataClassValues('V1_ALL_COUNTRIES');
            if (empty($viewListeAllCountries)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllCountries as $value) {
                $finalListeAllCountries[$value->COUNTRY_OID] = $value->COUNTRY_CODE . ' - ' . $value->COUNTRY_LABEL;
            }
            $_SESSION['finalListeAllCountries'] = $finalListeAllCountries;
        }
        return $_SESSION['finalListeAllCountries'];
    }

    public static function getAllContratsCode() {
        if (!isset($_SESSION['finalListeAllContratsCode'])) {
            $viewListeAllContrats = GepsisOdataReadClass::getOdataClassValues('V1_ALL_CONTRATS');
            if (empty($viewListeAllContrats)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllContrats as $value) {
                $finalListeAllContrats[$value->CONTR_O_ID] = $value->CONTR_CODE;
            }
            $_SESSION['finalListeAllContratsCode'] = $finalListeAllContrats;
        }
        return $_SESSION['finalListeAllContratsCode'];
    }

    public static function getAllContratsLabel() {
        if (!isset($_SESSION['finalListeAllContratsLabel'])) {
            $viewListeAllContrats = GepsisOdataReadClass::getOdataClassValues('V1_ALL_CONTRATS');
            if (empty($viewListeAllContrats)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllContrats as $value) {
                $finalListeAllContrats[$value->CONTR_O_ID] = $value->CONTR_LABEL;
            }
            $_SESSION['finalListeAllContratsLabel'] = $finalListeAllContrats;
        }
        return $_SESSION['finalListeAllContratsLabel'];
    }

    public static function getAllExamenType() {
        if (!isset($_SESSION['finalListeAllExamenType'])) {
            $customer = GepsisOdataReadClass::getOdataClassValues('V1_ALL_EXAMEN_TYPE');
            if (empty($customer)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            $finalListeAllExamenTypeArray = array();
            foreach ($customer as $value) {
                $finalListeAllExamenTypeArray[$value->EX_TYPE_O_ID] = $value->EX_TYPE_LABEL;
            }
            $_SESSION['finalListeAllExamenType'] = $finalListeAllExamenTypeArray;
        }
        return $_SESSION['finalListeAllExamenType'];
    }

    public static function getAllContratsVersCategory() {
        if (!isset($_SESSION['finalListeAllContratsVersCategory'])) {
            $viewAllContratsVersCategory = GepsisOdataReadClass::getOdataClassValues('V1_ALL_CONTRATS');
            if (empty($viewAllContratsVersCategory)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewAllContratsVersCategory as $value) {
                $finalListeAllContratsVersCategory[$value->CONTR_O_ID] = $value->CONTR_CATEGORY;
            }
            $_SESSION['finalListeAllContratsVersCategory'] = $finalListeAllContratsVersCategory;
        }
        return $_SESSION['finalListeAllContratsVersCategory'];
    }

    public static function getAllVoieTyp() {
        if (!isset($_SESSION['finalAllVoieTyp'])) {
            $viewAllVoieTyp = GepsisOdataReadClass::getOdataClassValues('V1_ALL_VOIE_TYP');
            if (empty($viewAllVoieTyp)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewAllVoieTyp as $value) {
                $finalAllVoieTyp[$value->VOIE_TYP_OID] = $value->VOIE_TYP_LABEL;
            }
            $_SESSION['finalAllVoieTyp'] = $finalAllVoieTyp;
        }
        return $_SESSION['finalAllVoieTyp'];
    }

    public static function getAllPostesPcs() {
        if (!isset($_SESSION['finalListeAllPostesPcs'])) {
            $viewListeAllPostesPcs = GepsisOdataReadClass::getOdataClassValues('V1_ALL_POSTES_PCS');
            if (empty($viewListeAllPostesPcs)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllPostesPcs as $value) {
                $finalListeAllPostesPcs[$value->POSTE_O_ID] = $value->POSTE_CODE;
            }
            $_SESSION['finalListeAllPostesPcs'] = $finalListeAllPostesPcs;
        }
        return $_SESSION['finalListeAllPostesPcs'];
    }

    public static function getAllPostesPcsLabel() {
        if (!isset($_SESSION['finalListeAllPostesPcsLabel'])) {
            $viewListeAllPostesPcsLabel = GepsisOdataReadClass::getOdataClassValues('V1_ALL_POSTES_PCS');
            if (empty($viewListeAllPostesPcsLabel)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllPostesPcsLabel as $value) {
                $finalListeAllPostesPcLabel[$value->POSTE_O_ID] = $value->POSTE_LABEL;
            }
            $_SESSION['finalListeAllPostesPcsLabel'] = $finalListeAllPostesPcLabel;
        }
        return $_SESSION['finalListeAllPostesPcsLabel'];
    }

    public static function getAllPostesPcsFull() {
        if (!isset($_SESSION['finalListeAllPostesPcsFull'])) {
            $viewListeAllPostesPcsFull = GepsisOdataReadClass::getOdataClassValues('V1_ALL_POSTES_PCS');
            if (empty($viewListeAllPostesPcsFull)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllPostesPcsFull as $value) {
                if (!strpos($value->POSTE_LABEL, 'POSTE GENERIQUE'))
                    $finalListeAllPostesPcFull[$value->POSTE_O_ID] = $value->POSTE_CODE . ' - ' . $value->POSTE_LABEL;
            }
            $_SESSION['finalListeAllPostesPcsFull'] = $finalListeAllPostesPcFull;

            foreach ($viewListeAllPostesPcsFull as $value) {
                if (!strpos($value->POSTE_LABEL, 'POSTE GENERIQUE'))
                    $finalListeAllPostesPcs[$value->POSTE_O_ID] = $value->POSTE_CODE;
            }
            $_SESSION['finalListeAllPostesPcs'] = $finalListeAllPostesPcs;
        }
        return $_SESSION['finalListeAllPostesPcsFull'];
    }

    public static function getAllEntrPostesPcs($entrOID) {
        $viewListeAllEntrPostesPcs = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_POSTES_PCS', "ENTR_O_ID eq " . $entrOID);
        if (empty($viewListeAllEntrPostesPcs)) {
            \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
            return;
        }

        $finalListeAllEntrPostesPcs = array();
        foreach ($viewListeAllEntrPostesPcs as $value) {
            if (strpos($value->POSTE_LABEL, 'POSTE GENERIQUE') == false)
                $finalListeAllEntrPostesPcs[$value->POSTE_ENTR_O_ID] = $value->POSTE_CODE;
        }
        return $finalListeAllEntrPostesPcs;
    }

    public static function getAllEntrPostesPcsLabel($entrOID) {
        $viewListeAllEntrPostesPcsLabel = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_POSTES_PCS', "ENTR_O_ID eq " . $entrOID);
        if (empty($viewListeAllEntrPostesPcsLabel)) {
            \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
            return;
        }

        $finalListeAllEntrPostesPcsLabel = array();
        foreach ($viewListeAllEntrPostesPcsLabel as $value) {
            if (strpos($value->POSTE_LABEL, 'POSTE GENERIQUE') == false)
                $finalListeAllEntrPostesPcsLabel[$value->POSTE_ENTR_O_ID] = $value->POSTE_LABEL;
        }
        return $finalListeAllEntrPostesPcsLabel;
    }

    public static function getAllEntrPostesPcsFull($entrOID) {
        $viewListeAllEntrPostesPcsFull = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_POSTES_PCS', "ENTR_O_ID eq " . $entrOID);

        if (empty($viewListeAllEntrPostesPcsFull)) {
            // vsm(t('No result returned. Please check your query and the endpoint status (getAllEntrPostesPcs).'));
            return;
        }

        $finalListeAllEntrPostesPcsFull = array();
        foreach ($viewListeAllEntrPostesPcsFull as $value) {
            if (strpos($value->POSTE_LABEL, 'POSTE GENERIQUE') == false)
                $finalListeAllEntrPostesPcsFull[$value->POSTE_ENTR_O_ID] = $value->POSTE_CODE . ' - ' . $value->POSTE_LABEL;
        }
        return $finalListeAllEntrPostesPcsFull;
    }

    public static function getAllTitres() {
        if (!isset($_SESSION['finalTitresTypes'])) {
            $viewListeAllTitres = GepsisOdataReadClass::getOdataClassValues('V1_ALL_TITRES');
            if (empty($viewListeAllTitres)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllTitres as $value) {
                $finalListeAllTitres[$value->TITRE_O_ID] = $value->TITRE_LABEL;
            }
            $_SESSION['finalTitresTypes'] = $finalListeAllTitres;
        }
        return $_SESSION['finalTitresTypes'];
    }

    public static function getAllTypesAdresses() {
        if (!isset($_SESSION['finalAllTypesAdresses'])) {
            $viewAllTypesAdresses = GepsisOdataReadClass::getOdataClassValues('V1_ALL_TYPES_ADDRESSES');
            if (empty($viewAllTypesAdresses)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewAllTypesAdresses as $value) {
                $finalTypesAdresses[$value->ADR_O_ID] = $value->ADR_LABEL;
            }
            $_SESSION['finalAllTypesAdresses'] = $finalTypesAdresses;
        }
        return $_SESSION['finalAllTypesAdresses'];
    }

    public static function getAllAdherentOidAdresses($entrOid) {
        $viewListeAdherentAdresses = GepsisOdataReadClass::getOdataClassValues('v1_entr_adresses', "ENTR_O_ID eq " . $entrOid);
        if (empty($viewListeAdherentAdresses)) {
            \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
            return;
        }
        foreach ($viewListeAdherentAdresses as $value) {
            $finalAdherentAdresses[$value->ADR_O_ID] = $value->ADR_LABEL . ': ' . $value->ADR_CITY_LABEL . ' - ' . $value->ADR_CITY_CODE;
            // . ' : ' . $value -> ADR_RUE1;
        }
        return $finalAdherentAdresses;
    }

    public static function getAllAdherentOidTypesAdressesWithType($entrOid) {
        $viewListeAdherentTypeAdresses = GepsisOdataReadClass::getOdataClassValues('v1_entr_adresses', "ENTR_O_ID eq " . $entrOid);
        if (empty($viewListeAdherentTypeAdresses)) {
            \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
            return;
        }
        foreach ($viewListeAdherentTypeAdresses as $value) {
            $finalAdherentTypeAdresses[$value->ADR_TYPE_O_ID] = $value->ADR_LABEL . ': ' . $value->ADR_CITY_LABEL . ' - ' . $value->ADR_CITY_CODE;
            // . ' : ' . $value -> ADR_RUE1;
        }
        return $finalAdherentTypeAdresses;
    }

    public static function getAllAdherentDetailsAdresses($adrOID) {
        $viewListeAdherentDetailsAdresses = GepsisOdataReadClass::getOdataClassValues('v1_entr_adresses', "ADR_O_ID eq " . $adrOID, 1);
        if (empty($viewListeAdherentDetailsAdresses)) {
            \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
            return;
        }
        foreach ($viewListeAdherentDetailsAdresses as $value) {
            $finalTypesAdherentListaDetailsAdresses[$value->ADR_O_ID] = $value->ADR_CITY_LABEL . ' - ' . $value->ADR_CITY_CODE . ' : ' . $value->ADR_RUE1;
        }
        return $finalTypesAdherentListaDetailsAdresses;
    }

    public static function getAllTypesRolesLabel() {
        if (!isset($_SESSION['finalListeAllTypesRolesLabel'])) {
            $viewListeAllTypesRolesLabel = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TYPES_ROLES');
            if (empty($viewListeAllTypesRolesLabel)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllTypesRolesLabel as $value) {
                if ($value != 'Newsletter')
                    $finalListeAllTypesRolesLabel[$value->TYPE_ROLE_O_ID] = $value->TYPE_ROLE_LABEL;
            }
            $_SESSION['finalListeAllTypesRolesLabel'] = $finalListeAllTypesRolesLabel;
        }
        return $_SESSION['finalListeAllTypesRolesLabel'];
    }

    public static function getAllNafOIdCode() {
        // unset($_SESSION['finalListeAllNafOIdCode']);
        if (!isset($_SESSION['finalListeAllNafOIdCode'])) {
            $viewListeAllNafs = GepsisOdataReadClass::getOdataClassValues('V1_ALL_NAF');
            if (empty($viewListeAllNafs)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllNafs as $value) {
                $finalListeAllNafs[$value->NAF_O_ID] = $value->NAF_CODE;
            }
            $_SESSION['finalListeAllNafOIdCode'] = $finalListeAllNafs;
        }
        return $_SESSION['finalListeAllNafOIdCode'];
    }

    public static function getAllNafOIdLabel() {
        // unset($_SESSION['finalListeAllNafOIdLabel']);
        if (!isset($_SESSION['finalListeAllNafOIdLabel'])) {
            $viewListeAllNafs = GepsisOdataReadClass::getOdataClassValues('V1_ALL_NAF');
            if (empty($viewListeAllNafs)) {
                \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
                return;
            }
            foreach ($viewListeAllNafs as $value) {
                $finalListeAllNafs[$value->NAF_O_ID] = $value->NAF_LABEL;
            }
            $_SESSION['finalListeAllNafOIdLabel'] = $finalListeAllNafs;
        }
        return $_SESSION['finalListeAllNafOIdLabel'];
    }

    public static function getFinalListeFullNaf() {
        $finalListeAllNafOIdCode = GetAllFunctions::getAllNafOIdCode();
        $finalListeAllNafOIdLabel = GetAllFunctions::getAllNafOIdLabel();
        $finalListeFullNaf = array();

        if (!empty($finalListeAllNafOIdCode) && !empty($finalListeAllNafOIdLabel)) {
            foreach ($finalListeAllNafOIdCode as $key => $value) {
                $finalListeFullNaf[$key] = $value . ' - ' . $finalListeAllNafOIdLabel[$key];
            }
        }
        return $finalListeFullNaf;
    }

    public static function getAllPostes($trav) {
        $viewListeAllPostes = GepsisOdataReadClass::getOdataClassValues('V1_ALL_POSTES', 'TRAV_O_ID eq ' . $trav);
        if (empty($viewListeAllPostes)) {
            \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
            return;
        }

        $finalListeAllPostes = array();
        foreach ($viewListeAllPostes as $key => $value) {
            $finalListeAllPostes[$key]['POSTE_CODE'] = $value->POSTE_CODE;
            $finalListeAllPostes[$key]['POSTE_LABEL'] = $value->POSTE_LABEL;
        }
        return $finalListeAllPostes;
    }
}

