create view v1$all_meetings_available as
select  distinct
    e.o_id as entreprise_id,
    m.o_id as meeting_id,
    md.o_id as meeting_detail_id,
    m.day::date,
    md.heure as start_time,
    cm.code as cabinet_code,
    labellang(cm.o_id, 'F') as cabinet_label,
    le.code as lieu_examen_code,
    labellang(le.o_id, 'F') as lieu_examen_label,
    (select string_agg(e.e_mail, ';') from e_mail e where e.owner=le.o_id) as lieu_emails,
    (select string_agg(e.numero, ', ') from phone_number e join phone_type pt on pt.o_id=e.type and pt.code='1' where e.owner=le.o_id) as lieu_tels,
    le_addr.lig1 as lieu_adr_lig1,
    le_addr.lig2 as lieu_adr_lig2,
    le_addr.lig3 as lieu_adr_lig3,
    le_addr.lig4 as lieu_adr_lig4,
    le_addr.lig5 as lieu_adr_lig5,
    le_addr.lig6 as lieu_adr_lig6
from
    meeting m
        join meeting_detail md on md.owner=m.o_id
        join alocated_resource ar on ar.owner=m.o_id
        join resource_table rt on rt.o_id=ar.resource_id
        join cabinet_medical cm on cm.o_id=rt.target
        join lieu_examen le on le.o_id=cm.owner
        join lateral (select * from address a where a.owner=le.o_id limit 1) le_addr on true
        join work_habit_scheme whs on whs.lieu_examen=le.o_id
        join entreprise e on e.o_id=whs.owner
where
        m.day >= now()
  and m.is_active='Y'
  --
  and ar.is_active='Y'
  and md.is_active='Y'
  and md.travailleur is null
  -- rule on cabinet_medical/lieu_examen
  and coalesce(get_context_value(cm.context, 'webVisible'), 'yes')='yes'
  and coalesce(get_context_value(le.context, 'webVisible'), 'yes')='yes'
  -- rule on meeting statut
  and (md.disponibility is null or md.disponibility not in (select o_id from meeting_detail_disponibility where code in ('I')))
;