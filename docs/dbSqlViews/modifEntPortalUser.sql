CREATE OR REPLACE VIEW webmed."v1$entr_portal_user"
AS
SELECT e.o_id AS entp_o_id,
       e.owner AS entp_owner,
       e.dt_modif AS entp_dt_modif,
       e.user_id AS entp_user_id,
       e.password AS entp_password,
       e.email_sent AS entp_email_sent,
       e.sent_to AS entp_sent_to,
       e.sent_date AS entp_sent_date,
       CASE
           WHEN (select x.group_role = gr.o_id from entreprise x, group_role gr where x.o_id=e.owner and gr.code='F') THEN 'Y'::character varying
           ELSE 'N'::character varying
           END::character varying(10) AS is_fille,
       (select ef.owner from entreprise_filiale ef join entreprise m on ef.owner=m.o_id join group_role gr on gr.o_id=m.group_role where e.owner = ef.entreprise and gr.code='M' limit 1) as entp_mother_o_id
FROM ent_portal_user e;