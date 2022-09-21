alter table examen_type add context text;


create view webmed.v1$all_rlp_meetings_available as
with params as (
--
-- default parameters
--
    select
        -- default time for a RLP meeting in minutes
        '15'::text    as minRlpMeetingTimeInMinutes,
        -- the selection interval
        '2 month'::interval as selectionIntervalSize,
        -- medecin codification array
        array['M'] as medecinCodes,
        -- infirmiere codification array
        array['INF'] as infirmiereCodes
)
select
    t.o_id as trav_id,
    mem.o_id as motif_examen_medical_id,
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
    trav t
        join examen_periodique ep on ep.owner=t.o_id and ep.owner_type='T'
        join examen_type et on et.o_id=ep.type
        join  MOTIF_EXAMEN_MEDICAL_TY_EX mx on mx.examen_type=et.o_id and mx.the_type=2
        join motif_examen_medical mem on mem.o_id=mx.owner
        join work_habit_scheme whs on whs.o_id=t.work_habit_scheme
        join lieu_examen le on le.o_id=whs.lieu_examen
        join lateral (select * from address a where a.owner=le.o_id limit 1) le_addr on true,
    meeting m
        join meeting_detail md on md.owner=m.o_id
        -- is in one cabinet of the lieu
        join alocated_resource car on car.owner=m.o_id
        join resource_table crt on car.resource_id=crt.o_id
        join cabinet_medical cm on cm.o_id=crt.target,
    --
    params
where
        m.is_active='Y'
  and m.day >= GREATEST(coalesce(to_date('01'||lpad(ep.next_examen::text, 6,'0'), 'ddmmyyyy') , now() + interval '1 day')::date, now()::date + interval '1 day')
  and m.day < GREATEST(coalesce(to_date('01'||lpad(ep.next_examen::text, 6,'0'), 'ddmmyyyy') , now() + interval '1 day')::date, now()::date + interval '1 day') + params.selectionIntervalSize
  and md.is_active='Y' and md.travailleur is null
  -- default time for a meeting is 15 - replace if need it
  and coalesce(EXTRACT(MINUTE FROM (md.end_heure- md.heure)), params.minRlpMeetingTimeInMinutes::integer) >= coalesce(get_context_value(et.context, 'minRlpMeetingTimeInMinutes'), params.minRlpMeetingTimeInMinutes)::integer
  and car.is_active='Y'
  and cm.owner=le.o_id
  -- add the meeting is for a medecin that at that day is active in the team
  and exists (
        select
        from
            alocated_resource ar
                join resource_table rt on ar.resource_id=rt.o_id
                join medecin med on med.o_id=rt.target
                join p_code_medecin pcm on pcm.o_id=med.type_medecin
                join entreprise_team te on te.medecin=med.o_id
        where
                ar.owner=m.o_id
          and ar.is_active='Y'
          -- clause who can give take care of RHP portal rendez-vous
          and pcm.code = any(
            case
                -- medecin
                when ep.next_prestataire_type='CMEDINF01' then params.medecinCodes
                -- infirmiere
                when ep.next_prestataire_type='CMEDINF02' then infirmiereCodes
                else array['']
                end
            )
          and te.owner=t.entreprise
          and te.start_date <= m.day
          and (te.end_date is null or te.end_date >=m.day)
    )
  --
  and mem.is_active='Y' and mem.is_web_visible='Y' and mem.motif_examen_medical_category='RLP'
  -- to not be able to choose if there is already one defined
  and not exists (
        select
        from
            meeting m
                join meeting_detail md on m.o_id=md.owner
                join motif_examen_medical memx on memx.o_id=md.motif_examen_medical
        where
                m.is_active='Y'
          and m.day >= now()::date
          and md.is_active='Y'
          and md.travailleur = t.o_id
          and memx.motif_examen_medical_category = mem.motif_examen_medical_category
    );




create view webmed.v1$all_rhp_meetings_available as
with params as (
--
-- default parameters
--
    select
        -- default time for a RLP meeting in minutes
        '15'::text    as minRlpMeetingTimeInMinutes,
        -- the selection interval
        '2 month'::interval as selectionIntervalSize,
        -- medecin codification array
        array['M'] as medecinCodes,
        -- infirmiere codification array
        array['INF'] as infirmiereCodes
)
select
    t.o_id as trav_id,
    mem.o_id as motif_examen_medical_id,
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
    trav t
        join work_habit_scheme whs on whs.o_id=t.work_habit_scheme
        join lieu_examen le on le.o_id=whs.lieu_examen
        join lateral (select * from address a where a.owner=le.o_id limit 1) le_addr on true,
    meeting m
        join meeting_detail md on md.owner=m.o_id
        -- is in one cabinet of the lieu
        join alocated_resource car on car.owner=m.o_id
        join resource_table crt on car.resource_id=crt.o_id
        join cabinet_medical cm on cm.o_id=crt.target,
    --
    motif_examen_medical mem,
    params
where
        m.is_active='Y'
  and m.day >= GREATEST(coalesce(t.start_date, now())::date, now()::date + interval '1 day')
  and m.day < GREATEST(coalesce(t.start_date, now())::date, now()::date + interval '1 day') + params.selectionIntervalSize
  and md.is_active='Y' and md.travailleur is null
  and car.is_active='Y'
  and cm.owner=le.o_id
  -- add the meeting is for a medecin that at that day is active in the team
  and exists (
        select
        from
            alocated_resource ar
                join resource_table rt on ar.resource_id=rt.o_id
                join medecin med on med.o_id=rt.target
                join p_code_medecin pcm on pcm.o_id=med.type_medecin
                join entreprise_team te on te.medecin=med.o_id
        where
                ar.owner=m.o_id
          and ar.is_active='Y'
          -- clause who can give take care of RHP portal rendez-vous
          and pcm.code = ANY(params.medecinCodes)
          and te.owner=t.entreprise
          and te.start_date <= m.day
          and (te.end_date is null or te.end_date >=m.day)
    )
  --
  and mem.is_active='Y' and mem.is_web_visible='Y' and mem.motif_examen_medical_category='RHP'
  -- to not be able to choose if there is already one defined
  and not exists (
        select
        from
            meeting m
                join meeting_detail md on m.o_id=md.owner
                join motif_examen_medical memx on memx.o_id=md.motif_examen_medical
        where
                m.is_active='Y'
          and m.day >= now()::date
          and md.is_active='Y'
          and md.travailleur = t.o_id
          and memx.motif_examen_medical_category = mem.motif_examen_medical_category
    );



