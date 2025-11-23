# GitHub Actions for Documentation Management

This directory contains an automated workflow for maintaining the CHANGELOG.md file.

## Workflow

### Update Release Documentation (`update-release-docs.yml`)

**Trigger**: Automatically runs when a new GitHub release is published.

**What it does**:
- Extracts the version number from the release tag
- Moves release notes from the GitHub release to CHANGELOG.md
- Creates a pull request with the changes

**Usage**:
1. Create a new release on GitHub with detailed release notes
2. Tag the release with semantic versioning (e.g., `v2.1.0`, `v2.2.0`)
3. The workflow automatically triggers and creates a PR
4. Review and merge the PR

**Note**: The ROADMAP.md file should be updated manually as needed.

**Example Release Notes Format**:
```markdown
### Added
- New feature A
- New feature B

### Changed
- Updated feature C
- Modified feature D

### Fixed
- Fixed bug E
```

## Permissions

The workflow requires the following permissions:
- `contents: write` - To create branches and commits
- `pull-requests: write` - To create pull requests

These are automatically provided via the `GITHUB_TOKEN` secret.

## Pull Request Strategy

The workflow creates pull requests instead of committing directly to the main branch. This approach:
- ✅ Allows for review before changes are merged
- ✅ Maintains audit trail of documentation changes
- ✅ Prevents accidental overwrites
- ✅ Works with branch protection rules

## Customization

### Changing PR Behavior

To auto-merge PRs (not recommended), you can add a step after PR creation:
```yaml
- name: Auto-merge PR
  run: gh pr merge --auto --squash "${{ steps.cpr.outputs.pull-request-number }}"
  env:
    GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

### Changing Branch Strategy

To commit directly instead of creating PRs, replace the "Create Pull Request" step with:
```yaml
- name: Commit changes
  run: |
    git config user.name "github-actions[bot]"
    git config user.email "github-actions[bot]@users.noreply.github.com"
    git add CHANGELOG.md ROADMAP.md
    git commit -m "docs: update for release ${{ steps.version.outputs.version }}"
    git push
```

## Troubleshooting

### Workflow doesn't trigger
- Ensure the release is "published", not just created as a draft
- Check that the tag format is correct (e.g., `v2.1.0`)

### PR creation fails
- Verify that Actions have write permissions in repository settings
- Check if branch protection rules are blocking the bot

### Changes not detected
- Verify that CHANGELOG.md and ROADMAP.md exist in the repository
- Check that the file formats match what the workflow expects

## Best Practices

1. **Always review PRs**: Even though automated, always review changes before merging
2. **Use semantic versioning**: Tag releases with proper semver format (v2.1.0, v2.2.0)
3. **Write clear release notes**: The changelog quality depends on your release notes
4. **Test in a fork first**: Test workflow changes in a fork before applying to main repo

## Maintenance

This workflow uses:
- `actions/checkout@v4` - May need updates for new GitHub features
- `peter-evans/create-pull-request@v6` - Check for newer versions periodically

Update dependencies by modifying the version numbers in the workflow file.

---

**Last Updated**: November 23, 2025
