SELECT row_number() OVER () AS id,
        s1.name AS username,
       s1.field_code_adherent_value AS adherent,
       s1.field_nom_adherent_value AS adherent_name,
       s2.name AS otheruser,
       s2.role AS otheruser_role
FROM ( SELECT ufd."name" ,
              fdfac.field_code_adherent_value ,
              fdfan.field_nom_adherent_value
       FROM users u
                join users_field_data ufd  on u.uid = ufd.uid
                JOIN user__field_adherents fdfa ON fdfa.entity_id = u.uid
                JOIN paragraph__field_code_adherent fdfac ON fdfac.entity_id = fdfa.field_adherents_target_id
                JOIN paragraph__field_nom_adherent fdfan ON fdfan.entity_id = fdfa.field_adherents_target_id) s1
         JOIN ( SELECT fdfac.field_code_adherent_value,
                       ufd.name,
                       ur.roles_target_id  AS role
                FROM paragraph__field_code_adherent fdfac
                         JOIN user__field_adherents fdfa ON fdfac.entity_id = fdfa.field_adherents_target_id
                         JOIN users u ON fdfa.entity_id = u.uid
                         join users_field_data ufd  on u.uid = ufd.uid
                         JOIN user__roles ur ON ur.entity_id = u.uid
                where not  ( ur.roles_target_id::text = ANY (ARRAY['assistante'::character varying::text, 'administrator'::character varying::text]))) s2
              ON s1.field_code_adherent_value::text = s2.field_code_adherent_value::text
WHERE s1.name::text <> s2.name::text
  and s1.name like 'sfcamil'
ORDER BY 3,5,6;