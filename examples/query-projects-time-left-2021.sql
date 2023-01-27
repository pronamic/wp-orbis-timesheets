SELECT
	company.id AS company_id,
	company.name AS company_name,
	project.id AS project_id,
	project.name AS project_name,
	project.start_date AS project_start_date,
	project.invoice_number AS project_invoice_number,
	project.number_seconds AS project_seconds,
	TIME_FORMAT( SEC_TO_TIME( project.number_seconds ),'%H:%i' ) AS project_time,
	summary.seconds_spent AS project_seconds_spent,
	TIME_FORMAT( SEC_TO_TIME( summary.seconds_spent ),'%H:%i' ) AS project_time_spent,
	project.number_seconds - summary.seconds_spent AS project_seconds_left,
	TIME_FORMAT( SEC_TO_TIME( project.number_seconds - summary.seconds_spent ), '%H:%i' ) AS project_time_left
FROM
	orbis_projects AS project
		LEFT JOIN
	orbis_companies AS company
			ON project.principal_id = company.id
		LEFT JOIN
	(
		SELECT
			project.id AS project_id,
			COALESCE( SUM( timesheet.number_seconds ), 0 ) AS seconds_spent
		FROM
			orbis_projects AS project
				LEFT JOIN
			orbis_hours_registration AS timesheet
					ON (
						project.id = timesheet.project_id
							AND
						timesheet.date <= '2021-12-31'
					)
		GROUP BY
			project.id
	) AS summary
			ON project.id = summary.project_id
WHERE
	project.invoice_number LIKE '21%'
		AND
	(
		project.name LIKE '%Strippenkaart%'
			OR
		project.invoice_number IN ( '2100881', '2100882' ) 
	)
		AND
	project.invoicable
GROUP BY
	project.id
		HAVING ( project.number_seconds - summary.seconds_spent ) > 0
;
