#!/bin/bash
# Phase 1 push script
# Run from: ~/Desktop/Antigravity/opsone-web/
# Usage: bash push_phase1.sh

cd "$(dirname "$0")" || exit 1

echo "=== Current branch and status ==="
git status --short
echo ""
echo "=== Last 3 commits ==="
git log --oneline -3
echo ""
echo "=== Pushing to GitHub ==="
git push origin main
echo ""
echo "=== Push complete ==="
