-- users
select u.uid, u.name ,fdfn.field_nom_value , fdfp.field_prenom_value, u.pass,u.mail,u.created,u.access,u.login,u.status,u.init,t.user_roles
from users u
left join field_data_field_nom fdfn on fdfn.entity_id = u.uid
left join field_data_field_prenom fdfp on fdfp.entity_id = u.uid
join lateral (
    select string_agg(name::text, ', ') as user_roles
    from users_roles ur
             join role r on r.rid =  ur.rid
    where ur.uid  = u.uid
    group by ur.uid
        ) t on true
where u.uid > 1
-- and u.name like '%cuccureddu%' -- 42011
order by created ;


-- adherents per user
select u.uid, u.name , t.user_adh, fdfac.field_adherent_code_value
from users u
         join field_data_field_adherent_code fdfac on fdfac.entity_id = u.uid
         left join lateral (
    select string_agg(field_adherent_code_collection_value::text, ', ') as user_adh
    from field_data_field_adherent_code_collection fdfacc
             join field_data_field_adherents fdfa on fdfa.field_adherents_value  =  fdfacc.entity_id
    where fdfa.entity_id  =  u.uid
    group by fdfa.entity_id
        ) t on true
where u.uid > 1
-- and u.name like 'sfcamil' -- 35757
order by created ;

-- subusers
select u.uid, u.name , t.user_subuser, t.user_created
from users u
         join lateral (
    select string_agg(u2.name::text, ', ') as user_subuser, string_agg(r.created::text, ', ') as user_created
    from relation r
             join field_data_endpoints fde on fde.entity_id = r.vid
             join users u2 on u2.uid = fde.endpoints_entity_id
    where r.uid = u.uid
      and fde.endpoints_entity_id <> u.uid
    group by u.uid
        ) t on true
where u.uid > 1
-- and u.name like 'sfcamil' -- 35757
order by created ;
