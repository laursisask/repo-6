package util

import (
	"github.com/google/uuid"
)

type RandomStringGenerator struct {
	Size int
}

func NewRandomStringGenerator() *RandomStringGenerator {

	return &RandomStringGenerator{
		Size: 36,
	}
}

func (gen *RandomStringGenerator) RandomString() string {
	var random, _ = uuid.NewRandom()
	return random.String()
}
