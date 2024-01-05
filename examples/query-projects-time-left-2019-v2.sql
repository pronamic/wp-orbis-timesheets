SELECT
	SUM( projects.project_billable_amount ) AS billable_amount,
	TIME_FORMAT( SEC_TO_TIME( SUM( projects.project_seconds_spent ) ), '%H:%i' ) AS time_spent,
	TIME_FORMAT( SEC_TO_TIME( SUM( projects.project_seconds_left ) ), '%H:%i' ) AS time_left,
	SUM( projects.project_available_amount ) AS available_amount
FROM (
	SELECT
		company.id AS company_id,
		company.name AS company_name,
		project.id AS project_id,
		project.name AS project_name,
		project.start_date AS project_start_date,
		project.invoice_number AS project_invoice_number,
		project.number_seconds AS project_seconds,
		TIME_FORMAT( SEC_TO_TIME( project.number_seconds ),'%H:%i' ) AS project_time,
		SUM( timesheet.number_seconds ) AS project_seconds_spent,
		TIME_FORMAT( SEC_TO_TIME( SUM( timesheet.number_seconds ) ),'%H:%i' ) AS project_time_spent,
		project.number_seconds - SUM( timesheet.number_seconds ) AS project_seconds_left,
		TIME_FORMAT( SEC_TO_TIME( project.number_seconds - SUM( timesheet.number_seconds ) ), '%H:%i' ) AS project_time_left,
		project.billable_amount AS project_billable_amount,
		( project.billable_amount / project.number_seconds ) * ( project.number_seconds - SUM( timesheet.number_seconds ) ) AS project_available_amount
	FROM
		orbis_projects AS project
			LEFT JOIN
		orbis_companies AS company
				ON project.principal_id = company.id
			LEFT JOIN
		orbis_hours_registration AS timesheet
				ON project.id = timesheet.project_id
	WHERE
		project.invoice_number LIKE '19%'
			AND
		project.name LIKE '%Strippenkaart%'
			AND
		timesheet.date <= '2019-12-31'
			AND
		project.invoicable
	GROUP BY
		project.id WITH ROLLUP
			HAVING ( project.number_seconds - SUM( timesheet.number_seconds ) ) > 0
) AS projects
;
