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
	TIME_FORMAT( SEC_TO_TIME( project.number_seconds - SUM( timesheet.number_seconds ) ), '%H:%i' ) AS project_time_left
FROM
	orbis_projects AS project
		LEFT JOIN
	orbis_companies AS company
			ON project.principal_id = company.id
		LEFT JOIN
	orbis_hours_registration AS timesheet
			ON project.id = timesheet.project_id
WHERE
	project.start_date BETWEEN '2016-01-01' AND '2016-12-31'
		AND
	project.name LIKE '%Strippenkaart%'
		AND
	timesheet.date < '2016-12-31'
GROUP BY
	project.id
		HAVING ( project.number_seconds - SUM( timesheet.number_seconds ) ) > 0
;
