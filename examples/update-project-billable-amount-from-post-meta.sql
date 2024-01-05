--
-- Select.
--
SELECT
	*
FROM
	orbis_projects AS project
		INNER JOIN
	wp_postmeta AS post_meta_price
			ON (
				post_meta_price.post_id = project.post_id
					AND
				post_meta_price.meta_key = '_orbis_price'
			)
WHERE
	project.billable_amount IS NULL
		AND
	post_meta_price.meta_value != ''
;

--
-- Update.
--

UPDATE
	orbis_projects AS project
		INNER JOIN
	wp_postmeta AS post_meta_price
			ON (
				post_meta_price.post_id = project.post_id
					AND
				post_meta_price.meta_key = '_orbis_price'
			)
SET
	project.billable_amount = post_meta_price.meta_value
WHERE
	project.billable_amount IS NULL
		AND
	post_meta_price.meta_value != ''
;
